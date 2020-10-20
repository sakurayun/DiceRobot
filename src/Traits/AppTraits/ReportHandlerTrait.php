<?php

declare(strict_types=1);

namespace DiceRobot\Traits\AppTraits;

use DiceRobot\Factory\ReportFactory;
use DiceRobot\Action\{EventAction, MessageAction};
use DiceRobot\Data\Report\{Event, InvalidReport, Message};
use DiceRobot\Data\Report\Message\{FriendMessage, GroupMessage, TempMessage};
use DiceRobot\Enum\AppStatusEnum;
use DiceRobot\Exception\{DiceRobotException, MiraiApiException};
use DiceRobot\Interfaces\Report;
use DiceRobot\Service\{ApiService, RobotService};
use DiceRobot\Util\Convertor;
use Psr\Container\ContainerInterface;
use Selective\Config\Configuration;

/**
 * Trait ReportHandlerTrait
 *
 * The report handler trait.
 *
 * @package DiceRobot\Traits
 */
trait ReportHandlerTrait
{
    /** @var ContainerInterface Container */
    protected ContainerInterface $container;

    /** @var Configuration Config */
    protected Configuration $config;

    /** @var ApiService API service */
    protected ApiService $api;

    /** @var RobotService Robot service */
    protected RobotService $robot;

    use StatusTrait;

    use RouteCollectorTrait;

    use StatisticsTrait;

    /**
     * Handle message and event report.
     *
     * @param string $reportContent
     */
    public function report(string $reportContent): void
    {
        $this->logger->info("Report started.");

        /** Validate */
        if (!is_object($reportData = json_decode($reportContent)))
        {
            $this->logger->error("Report failed, JSON decode error.");

            return;
        }
        elseif (!$this->validate($report = ReportFactory::create($reportData)))
        {
            $this->logger->info("Report skipped, unsupported report.");

            return;
        }

        try
        {
            /** Report */
            if ($report instanceof Event)
                $this->event($report);
            elseif ($report instanceof Message)
                $this->message($report);
        }
        // Call Mirai APIs failed
        catch (MiraiApiException $e)  // TODO: catch (MiraiApiException) in PHP 8
        {
            $this->logger->alert("Report failed, unable to call Mirai API.");
        }
    }

    /**
     * @param Event $event
     *
     * @noinspection PhpRedundantCatchClauseInspection
     */
    protected function event(Event $event): void
    {
        // Check application status
        if ($this->getStatus()->lessThan(AppStatusEnum::RUNNING()))
        {
            $this->logger->info("Report skipped. Application status {$this->getStatus()}.");

            return;
        }

        if (empty($actionName = $this->matchEvent($event)))
        {
            $this->logger->info("Report skipped, matching miss.");

            return;
        }

        /** @var EventAction $action */
        $action = $this->container->make($actionName, [
            "event" => $event
        ]);

        try
        {
            $action();

            $this->logger->info("Report finished.");
        }
            // Action interrupted, log error
        catch (DiceRobotException $e)
        {
            // TODO: $e::class, $action->event::class, $action::class in PHP 8
            $this->logger->error(
                "Report failed, " .
                get_class($e) .
                " occurred when handling " .
                get_class($action->event) .
                " and executing " .
                get_class($action)
            );
        }
    }

    /**
     * @param Message $message
     *
     * @throws MiraiApiException
     *
     * @noinspection PhpRedundantCatchClauseInspection
     */
    protected function message(Message $message): void
    {
        // Check application status
        if (!$this->getStatus()->equals(AppStatusEnum::RUNNING()))
        {
            $this->logger->info("Report skipped. Application status {$this->getStatus()}.");

            return;
        }

        if (!$message->parseMessageChain())
        {
            $this->logger->error("Report failed, parse message error.");

            return;
        }

        list($filter, $at) = $this->filter($message);

        if (!$filter)
        {
            $this->logger->info("Report skipped, filter miss.");

            return;
        }

        list($match, $order, $actionName) = $this->matchMessage($message);

        if (empty($actionName))
        {
            $this->logger->info("Report skipped, matching miss.");

            return;
        }

        /** @var MessageAction $action */
        $action = $this->container->make($actionName, [
            "message" => $message,
            "match" => $match,
            "order" => $order,
            "at" => $at
        ]);

        $this->addCount($match, get_class($message), $message->sender);

        if (!$action->checkActive())
        {
            $this->logger->info("Report finished, robot inactive.");

            return;
        }

        try
        {
            $action();

            // Send reply if set
            if (!empty($action->reply))
            {
                if ($action->message instanceof FriendMessage)
                    $this->api->sendFriendMessage(
                        $action->message->sender->id,
                        Convertor::toMessageChain($action->reply)
                    );
                elseif ($action->message instanceof GroupMessage)
                    $this->api->sendGroupMessage(
                        $action->message->sender->group->id,
                        Convertor::toMessageChain($action->reply)
                    );
                elseif ($action->message instanceof TempMessage)
                    $this->api->sendTempMessage(
                        $action->message->sender->id,
                        $action->message->sender->group->id,
                        Convertor::toMessageChain($action->reply)
                    );
            }

            $this->logger->info("Report finished.");
        }
        // Action interrupted, send error message to group/user
        catch (DiceRobotException $e)
        {
            if ($action->message instanceof FriendMessage)
                $this->api->sendFriendMessage(
                    $action->message->sender->id,
                    Convertor::toMessageChain($this->config->getString("errorMessage.{$e}"))
                );
            elseif ($action->message instanceof GroupMessage)
                $this->api->sendGroupMessage(
                    $action->message->sender->group->id,
                    Convertor::toMessageChain($this->config->getString("errorMessage.{$e}"))
                );
            elseif ($action->message instanceof TempMessage)
                $this->api->sendTempMessage(
                    $action->message->sender->id,
                    $action->message->sender->group->id,
                    Convertor::toMessageChain($this->config->getString("errorMessage.{$e}"))
                );

            // TODO: $e::class, $action->message::class, $action::class in PHP 8
            $this->logger->info(
                "Report finished, " . get_class($e) . " occurred when handling " .
                get_class($action->message) . " and executing " . get_class($action)
            );

            if (!empty($e->extraMessage))
                $this->logger->error("Extra message: {$e->extraMessage}.");
        }
    }

    /**
     * Validate the report.
     *
     * @param Report $report
     *
     * @return bool
     */
    protected function validate(Report $report): bool
    {
        return !($report instanceof InvalidReport);
    }

    /**
     * Filter the order.
     *
     * @param Message $message
     *
     * @return array Filter and at
     */
    protected function filter(Message $message): array
    {
        if (preg_match(
            "/^(?:\[mirai:at:([1-9][0-9]*),.*?])?\s*[.。]\s*([\S\s]+)/",
            (string) $message, $matches
        )) {
            $message->message = "." . trim($matches[2]);

            $targetId = $matches[1];
            $at = $targetId == (string) $this->robot->getId();

            // At others
            if (!empty($targetId) && !$at)
                return [false, NULL];

            return [true, $at];
        }

        return [false, NULL];
    }
}