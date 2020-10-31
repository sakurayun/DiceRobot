<?php

declare(strict_types=1);

namespace DiceRobot\Traits;

use DiceRobot\Data\Report\{Event, Message};

/**
 * Trait RouteCollectorTrait
 *
 * The route collector trait.
 *
 * @package DiceRobot\Traits
 */
trait RouteCollectorTrait
{
    /** @var string[] Event routes */
    protected array $eventRoutes = [];

    /** @var array[] Message routes */
    protected array $messageRoutes = [];

    /**
     * Register event and message routes.
     *
     * @param array $routes The routes
     */
    public function registerRoutes(array $routes): void
    {
        $this->eventRoutes = $routes["event"] ?? [];
        $this->messageRoutes = $routes["message"] ?? [];
    }

    /**
     * Match event action.
     *
     * @param Event $event The event
     *
     * @return string|null Event action name or null
     */
    protected function matchEvent(Event $event): ?string
    {
        foreach ($this->eventRoutes as $eventType => $actionName) {
            if (get_class($event) == $eventType) {
                return $actionName;
            }
        }

        return null;
    }

    /**
     * @param Message $message The message
     *
     * @return array|null Match, order and message action name, or null
     */
    protected function matchMessage(Message $message): ?array
    {
        foreach ($this->messageRoutes as $_ => $routes) {
            foreach ($routes as $match => $actionName) {
                if (preg_match("/^\.{$match}\s*([\S\s]*)$/i", (string) $message, $matches)) {
                    return [$match, $matches[1], $actionName];
                }
            }
        }

        return null;
    }
}