<?php
namespace DiceRobot\Action\Message\RobotCommand;

use DiceRobot\Base\Customization;
use DiceRobot\Base\RobotCommandAction;
use DiceRobot\Base\RobotSettings;

/**
 * Class Stop
 *
 * Action class of order ".robot stop". Set robot inactive.
 */
final class Stop extends RobotCommandAction
{
    public function __invoke(): void
    {
        if ($this->commandValue == "" || (is_numeric($this->commandValue) &&
                (intval($this->commandValue) == $this->selfId ||
                    $this->commandValue == substr($this->selfId, -4))))
        {
            RobotSettings::setSetting("active", false);
            $this->reply = Customization::getCustomReply("robotCommandStop");
        }
        else $this->noResponse();
    }
}
