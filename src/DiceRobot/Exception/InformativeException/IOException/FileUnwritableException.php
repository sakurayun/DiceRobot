<?php
namespace DiceRobot\Exception\InformativeException\IOException;

use DiceRobot\Exception\InformativeException;
use DiceRobot\Service\Customization;

/**
 * File is unwritable. This exception will send reply "IOFileUnwritable".
 */
class FileUnwritableException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getReply("IOFileUnwritable"));
    }
}