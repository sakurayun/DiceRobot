<?php

declare(strict_types=1);

namespace DiceRobot\Data\Resource;

use DiceRobot\Data\Resource;

use const DiceRobot\DEFAULT_CONFIG;

/**
 * Class Config
 *
 * Resource container. Panel config.
 *
 * @package DiceRobot\Data\Resource
 */
class Config extends Resource
{
    /**
     * Set config.
     *
     * @param array $config Config data.
     *
     * @return bool Success.
     */
    public function setConfig(array $config): bool
    {
        if (!$this->checkConfig($config)) {
            return false;
        }

        $this->data = $this->checkValue(array_replace_recursive($this->data, $config));

        return true;
    }

    /**
     * Check whether the config is valid.
     *
     * @param array $config Config data.
     *
     * @return bool Validity.
     */
    protected function checkConfig(array $config): bool
    {
        // Only accept "strategy", "order", "reply" and "errMsg"
        foreach ($config as $key => $value) {
            if (!is_array($value)) {
                return false;
            }

            if ($key === "strategy") {
                foreach ($value as $itemValue) {
                    if (!is_bool($itemValue) && !is_null($itemValue)) {
                        return false;
                    }
                }
            } elseif ($key === "order") {
                foreach ($value as $itemValue) {
                    if (!is_int($itemValue) && !is_null($itemValue)) {
                        return false;
                    }
                }
            } elseif ($key === "reply" || $key === "errMsg") {
                foreach ($value as $itemValue) {
                    if (!is_string($itemValue) && !is_null($itemValue)) {
                        return false;
                    }
                }
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Check config value.
     *
     * @param array $config Config data.
     *
     * @return array Checked config.
     */
    protected function checkValue(array $config): array
    {
        foreach ($config as $key => $value) {
            foreach ($value as $itemKey => $itemValue) {
                if ($itemValue === DEFAULT_CONFIG[$key][$itemKey] || is_null($itemValue)) {
                    unset($config[$key][$itemKey]);
                }
            }
        }

        return $config;
    }
}
