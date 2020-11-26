<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Data\Dice;
use DiceRobot\Data\Report\Message\GroupMessage;
use DiceRobot\Exception\RepeatTimeOverstepException;
use DiceRobot\Exception\DiceException\{DiceNumberOverstepException, ExpressionErrorException,
    ExpressionInvalidException, SurfaceNumberOverstepException};
use DiceRobot\Util\Convertor;

/**
 * Class Dicing
 *
 * Roll a dice determined by dicing expression.
 *
 * @order r
 *
 *      Sample: .rd
 *              .r 6D90
 *              .rh
 *              .rh (5D80K2+10)x5 Reason
 *              .rs
 *              .rs (D60+5)*2 Reason
 *              .rb
 *              .rb3 Reason
 *              .rp
 *              .rp5 Reason
 *              .rd#4
 *              .rh (8DK3+10)x5 Reason#3
 *              .rb#5
 *              .rp#2
 *
 * @package DiceRobot\Action\Message
 */
class Dicing extends MessageAction
{
    /**
     * @inheritDoc
     *
     * @throws DiceNumberOverstepException|ExpressionErrorException|ExpressionInvalidException
     * @throws RepeatTimeOverstepException|SurfaceNumberOverstepException
     */
    public function __invoke(): void
    {
        list($expression, $repeat) = $this->parseOrder();

        $this->checkRange($repeat);

        list($vType, $reason, $detail) = $this->dicing($expression, $repeat);

        $reply = Convertor::toCustomString(
            $this->config->getReply(empty($reason) ? "dicingResult" : "dicingResultWithReason"),
            [
                "原因" => $reason,
                "昵称" => $this->getNickname(),
                "掷骰结果" => $detail
            ]
        );

        if ($vType === "H") {
            if ($this->message instanceof GroupMessage) {
                $this->sendPrivateMessageAsync(Convertor::toCustomString(
                    $this->config->getReply("dicingPrivateResult"),
                    [
                        "群名" => $this->message->sender->group->name,
                        "群号" => $this->message->sender->group->id,
                        "掷骰详情" => $reply
                    ]
                ));

                $this->setReply(empty($reason) ? "dicingPrivate" : "dicingPrivateWithReason", [
                    "原因" => $reason,
                    "昵称" => $this->getNickname(),
                    "掷骰次数" => $repeat
                ]);
            } else {
                $this->setReply("dicingPrivateNotInGroup");
            }
        } else {
            $this->setRawReply($reply);
        }
    }

    /**
     * @inheritDoc
     *
     * @return array Parsed elements
     */
    protected function parseOrder(): array
    {
        preg_match("/^([\S\s]*?)(?:#([1-9][0-9]*))?$/", $this->order, $matches);
        $expression = $matches[1];
        $repeat = empty($matches[2]) ? 1 : (int) $matches[2];

        /**
         * @var string $expression Dicing expression
         * @var int $repeat Count of repetition
         */
        return [$expression, $repeat];
    }

    /**
     * Check the range.
     *
     * @param int $repeat Repeat count
     *
     * @throws RepeatTimeOverstepException
     */
    protected function checkRange(int $repeat): void
    {
        if ($repeat < 1 || $repeat > $this->config->getOrder("maxRepeatTimes")) {
            throw new RepeatTimeOverstepException();
        }
    }

    /**
     * Execute dicing order.
     *
     * @param string $expression Dicing expression
     * @param int $repeat Repeat count
     *
     * @return array Dicing reason and detail
     *
     * @throws DiceNumberOverstepException|ExpressionErrorException|ExpressionInvalidException
     * @throws SurfaceNumberOverstepException
     */
    protected function dicing(string $expression, int $repeat): array
    {
        $detail = $repeat > 1 ? "\n" : "";
        /** @var Dice[] $dices */
        $dices = [];

        for ($i = 0; $i < $repeat; $i++) {
            $dices[$repeat] = isset($dices[$repeat + 1]) ?
                clone $dices[$repeat + 1] :
                new Dice($expression, $this->chatSettings->getInt("defaultSurfaceNumber"));
            $detail .= $dices[$repeat]->getCompleteExpression() . "\n";
        }

        // Simplify the reply
        if (mb_strlen($detail) > $this->config->getOrder("maxReplyCharacter")) {
            $detail = "";

            for ($i = 0; $i < $repeat; $i++) {
                $detail .= $dices[$repeat]->getCompleteExpression(true) . "\n";
            }
        }

        return [$dice->vType ?? null, $dice->reason ?? "", rtrim($detail)];
    }
}
