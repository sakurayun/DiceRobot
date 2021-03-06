<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\{Group, Operator};
use DiceRobot\Data\Report\Event;

/**
 * Class GroupEntranceAnnouncementChangeEvent
 *
 * DTO. Event of that the group's entrance announcement has changed.
 *
 * @package DiceRobot\Data\Report\Event
 *
 * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/EventType.md#%E6%9F%90%E7%BE%A4%E5%85%A5%E7%BE%A4%E5%85%AC%E5%91%8A%E6%94%B9%E5%8F%98
 */
final class GroupEntranceAnnouncementChangeEvent extends Event
{
    /** @var string Original entrance announcement. */
    public string $origin;

    /** @var string Current entrance announcement. */
    public string $current;

    /** @var Group The group. */
    public Group $group;

    /** @var Operator|null The operator, null if the operator is the robot. */
    public ?Operator $operator;
}
