<?php

namespace MAA\Toml\Nodes;

use MAA\Toml\TomlLocalTime;

/**
 * @internal
 */
final class LocalTimeNode implements Node, TomlDateTimeNode, ValuableNode
{
    public $value;

    /**
     * @param TomlLocalTime $value
     */
    public function __construct(TomlLocalTime $value)
    {
        $this->value = $value;
    }
}
