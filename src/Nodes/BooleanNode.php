<?php

namespace MAA\Toml\Nodes;

/**
 * @internal
 */
final class BooleanNode implements Node, ValuableNode
{
    public $value;
    public function __construct(bool $value)
    {
        $this->value = $value;
    }
}
