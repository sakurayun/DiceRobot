<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Data\Dice;
use DiceRobot\Data\Report\Message\GroupMessage;
use DiceRobot\Exception\{DiceRobotException, OrderErrorException};
use DiceRobot\Exception\FileException\LostException;
use DiceRobot\Util\Convertor;

/**
 * Class Dnd
 *
 * Generate character card of adventurer.
 *
 * @order dnd
 *
 *      Sample: .dnd
 *              .dnd8
 *
 * @package DiceRobot\Action\Message
 */
class Dnd extends MessageAction
{
    /** @var string DND generate rule */
    protected const DND_GENERATE_RULE = "4D6K3";

    /**
     * @inheritDoc
     *
     * @throws DiceRobotException|OrderErrorException
     */
    public function __invoke(): void
    {
        list($generateCount) = $this->parseOrder();

        if (!$this->checkRange($generateCount)) {
            return;
        }

        $atSender = ($this->message instanceof GroupMessage) ? "[mirai:at:{$this->message->sender->id}] " : "";

        $this->setReply("dndGenerateCardHeading", [
            "@发送者" => $atSender,
            "发送者QQ" => $this->message->sender->id,
            "冒险者属性" => $this->generateAttributes($generateCount)
        ]);
    }

    /**
     * @inheritDoc
     *
     * @return array Parsed elements
     *
     * @throws OrderErrorException
     */
    protected function parseOrder(): array
    {
        if (!preg_match("/^([1-9][0-9]*)?$/", $this->order, $matches)) {
            throw new OrderErrorException;
        }

        $generateCount = empty($matches[1]) ? 1 : (int) $matches[1];

        /**
         * @var int $generateCount Count of generation
         */
        return [$generateCount];
    }

    /**
     * Check the range.
     *
     * @param int $generateCount Count of generation
     *
     * @return bool Validity
     */
    protected function checkRange(int $generateCount): bool
    {
        $maxGenerateCount = $this->config->getOrder("maxGenerateCount");

        if ($generateCount < 1 || $generateCount > $maxGenerateCount) {
            $this->setReply("dndGenerateCardCountOverstep", [
                "最大生成次数" => $maxGenerateCount
            ]);

            return false;
        }

        return true;
    }

    /**
     * Generate attributes of character card.
     *
     * @param int $count Generate count
     *
     * @return string Attributes
     *
     * @throws LostException
     */
    protected function generateAttributes(int $count): string
    {
        $attributes = "";
        $attributesTemplate =
            $this->resource->getReference("DNDCharacterCardTemplate")->getString("templates.attributes");

        while ($count--) {
            $results = [];

            for ($i = 0; $i < 6; $i++) {
                $dice = isset($dice) ? clone $dice : new Dice(self::DND_GENERATE_RULE);
                $results[$i] = $dice->result;
            }

            $attributes .= Convertor::toCustomString($attributesTemplate, [
                "力量" => $results[0],
                "体质" => $results[1],
                "敏捷" => $results[2],
                "智力" => $results[3],
                "感知" => $results[4],
                "魅力" => $results[5],
                "属性总和" => array_sum($results)
            ]);
            $attributes .= "\n";
        }

        return rtrim($attributes);
    }
}
