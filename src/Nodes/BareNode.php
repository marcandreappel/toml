<?php

namespace MAA\Toml\Nodes;

/**
 * @internal
 */
final class BareNode implements Node
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
