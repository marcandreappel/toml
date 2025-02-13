<?php

namespace MAA\Toml\Nodes;

/**
 * @internal
 */
final class StringNode implements Node, ValuableNode
{
    public $value;

    /**
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
