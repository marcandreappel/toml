<?php

namespace MAA\Toml\Nodes;

/**
 * @internal
 */
final class KeyNode implements Node
{
    private $keys;

    /**
     * @param  BareNode[]|StringNode[]  $keys
     */
    public function __construct(array $keys)
    {
        $this->keys = $keys;
    }

    /**
     * @param  BareNode|StringNode $key
     */
    public function addKey(mixed $key): void
    {
        $this->keys[] = $key;
    }

    public function keys(): array
    {
        return $this->keys;
    }
}
