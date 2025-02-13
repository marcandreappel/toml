<?php

namespace MAA\Toml\Nodes;

/**
 * @internal
 */
final class RootTableNode implements Node
{
    protected $elements;

    /**
     * @param  KeyValuePairNode[] | TableNode[] | ArrayTableNode[]  $elements
     */
    public function __construct(array $elements)
    {
        $this->elements = $elements;
    }

    /**
     * @param KeyValuePairNode|TableNode|ArrayTableNode $element
     */
    public function addElement($element): void
    {
        $this->elements[] = $element;
    }

    public function elements(): array
    {
        return $this->elements;
    }
}
