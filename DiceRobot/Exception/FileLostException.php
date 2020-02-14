<?php
namespace DiceRobot\Exception;

use DiceRobot\Base\Customization;

/**
 * Class FileLostException
 *
 * Exception thrown when file is lost. This exception will send reply "_generalFileLostError".
 */
final class FileLostException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getCustomReply("_generalFileLostError"));
    }
}
