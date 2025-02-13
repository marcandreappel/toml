<?php

namespace MAA\Toml\Nodes;

/**
 * @internal
 */
final class InlineTableNode implements Node, ValuableNode
{
    private $elements;
    /**
     * @param  KeyValuePairNode[]  $elements
     */
    public function __construct(array $elements)
    {
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
