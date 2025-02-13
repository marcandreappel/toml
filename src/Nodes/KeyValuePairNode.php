<?php

namespace MAA\Toml\Nodes;

/**
 * @internal
 */
final class KeyValuePairNode implements Node
{
    public $key;
    public $value;

    public function __construct(KeyNode $key, ValuableNode $value)
    {
        $this->key = $key;
        $this->value = $value;
    }
}
