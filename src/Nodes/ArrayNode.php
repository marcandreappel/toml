<?php

namespace MAA\Toml\Nodes;

/**
 * @internal
 */
final class ArrayNode implements Node, ValuableNode
{
    private $elements;

    /**
     * @param  Node[]  $elements
     */
    public function __construct(array $elements)
    {
        $this->elements = $elements;
    }

    public function addElement(ValuableNode $element): void
    {
        $this->elements[] = $element;
    }

    public function elements(): array
    {
        return $this->elements;
    }
}
