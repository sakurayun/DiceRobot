<?php
namespace DiceRobot\Exception\InformativeException\DiceException;

use DiceRobot\Exception\InformativeException;
use DiceRobot\Service\Customization;

/**
 * Dice surface number oversteps the limit. This exception will send reply "diceSurfaceNumberOverstep".
 */
class SurfaceNumberOverstepException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getReply("diceSurfaceNumberOverstep"));
    }
}