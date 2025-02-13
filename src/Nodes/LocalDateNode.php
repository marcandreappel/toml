<?php

namespace MAA\Toml\Nodes;

use MAA\Toml\TomlLocalDate;

/**
 * @internal
 */
final class LocalDateNode implements Node, TomlDateTimeNode, ValuableNode
{
    public $value;

    /**
     * @param TomlLocalDate $value
     */
    public function __construct(TomlLocalDate $value)
    {
        $this->value = $value;
    }
}
