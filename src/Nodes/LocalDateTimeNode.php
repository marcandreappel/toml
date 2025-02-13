<?php

namespace MAA\Toml\Nodes;

use MAA\Toml\TomlLocalDateTime;

/**
 * @internal
 */
final class LocalDateTimeNode implements Node, TomlDateTimeNode, ValuableNode
{
    public $value;

    /**
     * @param TomlLocalDateTime $value
     */
    public function __construct(TomlLocalDateTime $value)
    {
        $this->value = $value;
    }
}
