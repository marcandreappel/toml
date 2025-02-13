<?php

namespace MAA\Toml\Nodes;

/**
 * @internal
 */
final class IntegerNode implements Node, NumericNode, ValuableNode
{
    public $value;
    public function __construct(int $value)
    {
        $this->value = $value;
    }
}
