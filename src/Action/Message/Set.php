<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;

/**
 * Class Set
 *
 * Set default dice surface number.
 *
 * @order set
 *
 *      Sample: .set
 *              .set 50
 *
 * @package DiceRobot\Action\Message
 */
class Set extends MessageAction
{
    /**
     * @inheritDoc
     */
    public function __invoke(): void
    {
        list($defaultSurfaceNumber) = $this->parseOrder();

        if (!$this->checkRange($defaultSurfaceNumber)) {
            return;
        }

        if ($defaultSurfaceNumber) {
            // Set the default dice surface number of this chat
            $this->chatSettings->set("defaultSurfaceNumber", $defaultSurfaceNumber);

            $this->setReply("setSurfaceNumberSet", [
                "默认骰子面数" => $defaultSurfaceNumber
            ]);
        } else {
            // Reset the default dice surface number of this chat to the default value of the robot
            $this->chatSettings->set("defaultSurfaceNumber", null);

            $this->setReply("setSurfaceNumberReset");
        }
    }

    /**
     * @inheritDoc
     *
     * @return array Parsed elements
     */
    protected function parseOrder(): array
    {
        if (!preg_match("/^([1-9][0-9]*)?$/", $this->order, $matches)) {
            return [-1];
        }

        $defaultSurfaceNumber = empty($matches[1]) ? null : (int) $matches[1];

        /**
         * @var int|null $defaultSurfaceNumber Default dice surface number
         */
        return [$defaultSurfaceNumber];
    }

    /**
     * Check the range.
     *
     * @param int|null $defaultSurfaceNumber Default dice surface number
     *
     * @return bool Validity
     */
    protected function checkRange(?int $defaultSurfaceNumber): bool
    {
        $maxSurfaceNumber = $this->config->getOrder("maxSurfaceNumber");

        if (!is_null($defaultSurfaceNumber) && ($defaultSurfaceNumber < 1 || $defaultSurfaceNumber > $maxSurfaceNumber)) {
            $this->setReply("setSurfaceNumberInvalid", [
                "最大骰子面数" => $maxSurfaceNumber
            ]);

            return false;
        }

        return true;
    }
}
