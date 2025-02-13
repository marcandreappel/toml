<?php

namespace MAA\Toml\Nodes;

/**
 * @internal
 */
final class FloatNode implements Node, NumericNode, ValuableNode
{
    public $value;

    /**
     * @param float|string $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }
}
