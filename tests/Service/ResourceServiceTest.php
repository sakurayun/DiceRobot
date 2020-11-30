<?php

declare(strict_types=1);

namespace DiceRobot\Tests;

use DiceRobot\Data\Config;
use DiceRobot\Exception\RuntimeException;
use DiceRobot\Service\ResourceService;

class ResourceServiceTest extends TestCase
{
    public function testInitialize(): void
    {
        $container = $this->getContainer();
        $service = $container->get(ResourceService::class);
        $config = $container->get(Config::class);

        try {
            $service->initialize($config);

            $success = true;
        } catch (RuntimeException $e) {
            $success = false;
        }

        $this->assertEquals(true, $success);
    }
}
