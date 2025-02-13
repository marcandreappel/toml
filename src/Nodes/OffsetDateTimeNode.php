<?php

namespace MAA\Toml\Nodes;

use MAA\Toml\TomlDateTime;

/**
 * @internal
 */
final class OffsetDateTimeNode implements Node, TomlDateTimeNode, ValuableNode
{
    public $value;

    /**
     * @param TomlDateTime $value
     */
    public function __construct(TomlDateTime $value)
    {
        $this->value = $value;
    }
}
