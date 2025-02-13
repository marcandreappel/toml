<?php

namespace MAA\Toml\Nodes;

/**
 * @internal
 */
final class FloatNode implements Node, NumericNode, ValuableNode
{
    public $value;
    public function __construct(mixed $value)
    {
        $this->value = $value;
    }
}
