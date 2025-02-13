<?php

namespace MAA\Toml\Nodes;

/**
 * @internal
 */
class TableNode implements Node
{
    public $key;
    private $elements;

    /**
     * @param  KeyNode  $key
     * @param  KeyValuePairNode[]  $elements
     */
    public function __construct(KeyNode $key, array $elements)
    {
        $this->key = $key;
        $this->elements = $elements;
    }

    public function addElement(KeyValuePairNode $element): void
    {
        $this->elements[] = $element;
    }

    public function elements(): array
    {
        return $this->elements;
    }
}
