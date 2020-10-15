<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Exception\OrderErrorException;
use DiceRobot\Exception\ApiException\{InternalErrorException, NetworkErrorException, UnexpectedErrorException};
use DiceRobot\Util\Convertor;

/**
 * Class Jrrp
 *
 * Send message sender's luck of today.
 *
 * @order jrrp
 *
 *      Sample: .jrrp
 *
 * @package DiceRobot\Action\Message
 */
class Jrrp extends MessageAction
{
    /**
     * @inheritDoc
     *
     * @throws InternalErrorException|NetworkErrorException|OrderErrorException|UnexpectedErrorException
     */
    public function __invoke(): void
    {
        $this->parseOrder();

        $this->reply =
            Convertor::toCustomString(
                $this->config->getString("reply.jrrpReply"),
                [
                    "昵称" => $this->getNickname(),
                    "人品" => $this->api->jrrp($this->message->sender->id)->jrrp
                ]
            );
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
        if (!preg_match("/^$/", $this->order))
            throw new OrderErrorException;

        return [];
    }
}