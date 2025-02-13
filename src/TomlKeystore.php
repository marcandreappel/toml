<?php

namespace MAA\Toml;

use MAA\Toml\Nodes\ArrayTableNode;
use MAA\Toml\Nodes\BareNode;
use MAA\Toml\Nodes\KeyNode;
use MAA\Toml\Nodes\KeyValuePairNode;
use MAA\Toml\Nodes\StringNode;
use MAA\Toml\Nodes\TableNode;
use Ds\Set;

/**
 * @internal
 */
final class TomlKeystore
{
    /** @var Set */
    private $keys;

    /** @var Set */
    private $tables;

    /** @var Set */
    private $arrayTables;

    /** @var Set */
    private $implicitTables;

    public function __construct()
    {
        $this->keys = new Set;
        $this->tables = new Set;
        $this->arrayTables = new Set;
        $this->implicitTables = new Set;
    }

    /**
     * @param KeyValuePairNode|TableNode|ArrayTableNode $node
     * @throws TomlError
     */
    public function addNode($node): void
    {
        $nodeClass = get_class($node);
        switch ($nodeClass) {
            case KeyValuePairNode::class:
                $this->addKeyValuePairNode($node);
                break;
            case TableNode::class:
                $this->addTableNode($node);
                break;
            case ArrayTableNode::class:
                $this->addArrayTableNode($node);
                break;
            default:
                throw new TomlError('unsupported Node');
        }
    }

    /**
     * @throws TomlError
     */
    protected function addKeyValuePairNode(KeyValuePairNode $node): void
    {
        $key = '';

        if (! $this->tables->isEmpty()) {
            $table = $this->tables->last();
            $key = "$table.";
        }

        $components = $this->makeKeyComponents($node->key);
        $counter = count($components);

        for ($i = 0; $i < $counter; $i++) {
            $component = $components[$i];

            $key .= ($i !== 0 ? '.' : '').$component;

            if ($this->keysContains($key) || $this->tablesContains($key) || $this->tablesContainsZeroIndex($key)) {
                throw new TomlError('key duplication');
            }

            if (count($components) > 1 && $i < count($components) - 1) {
                $this->implicitTablesAdd($key);

                continue;
            }

            if ($this->implicitTablesContains($key)) {
                throw new TomlError('key duplication');
            }
        }

        $this->keysAdd($key);
    }

    /**
     * @return array
     */
    protected function makeKeyComponents(KeyNode $keyNode): array
    {
        return array_map(function ($key) {
            return $key->value;
        }, $keyNode->keys());
    }

    protected function keysContains(string $key): bool
    {
        return $this->keys->contains($key);
    }

    protected function tablesContains(string $key): bool
    {
        return $this->tables->contains($key);
    }

    protected function tablesContainsZeroIndex(string $key): bool
    {
        return $this->tables->contains("$key.[0]");
    }

    protected function implicitTablesAdd(string $key): void
    {
        $this->implicitTables->add($key);
    }

    protected function implicitTablesContains(string $key): bool
    {
        return $this->implicitTables->contains($key);
    }

    protected function keysAdd(string $key): void
    {
        $this->keys->add($key);
    }

    /**
     * @throws TomlError
     */
    protected function addTableNode(TableNode $tableNode): void
    {
        $components = $this->makeKeyComponents($tableNode->key);
        $header = $this->makeKey($tableNode->key);
        $arrayTable = $this->arrayTables->reversed();
        $foundArrayTable = null;

        foreach ($arrayTable as $arrayTableItem) {
            if ($this->startsWith($header, $this->makeHeaderFromArrayTable($arrayTableItem))) {
                $foundArrayTable = $arrayTableItem;

                break;
            }
        }

        $key = '';

        if ($foundArrayTable !== null) {
            $foundArrayTableHeader = $this->makeHeaderFromArrayTable($foundArrayTable);

            $components = array_filter(
                $this->unescapedExplode('.', substr($header, strlen($foundArrayTableHeader))),
                function (string $component) {
                    return $component !== '';
                }
            );

            if ($components === []) {
                throw new TomlError('broken key');
            }

            $key = "$foundArrayTable.";
        }

        $i = 0;
        foreach ($components as $component) {
            $component = str_replace('.', '\.', $component);

            $key .= ($i !== 0 ? '.' : '').$component;

            $i++;

            if ($this->keysContains($key)) {
                throw new TomlError('key duplication');
            }
        }

        if ($this->arrayTablesContains($key) || $this->tablesContains($key) || $this->implicitTablesContains($key)) {
            throw new TomlError('key duplication');
        }

        $this->tables->add($key);
    }

    protected function makeKey(KeyNode $keyNode): string
    {
        return implode('.', $this->makeKeyComponents($keyNode));
    }

    protected function makeHeaderFromArrayTable(string $arrayTable): string
    {
        return implode(
            '.',
            array_filter(
                $this->unescapedExplode('.', $arrayTable),
                function ($item) {
                    return !$this->startsWith((string) $item, '[');
                }
            )
        );
    }

    protected function unescapedExplode(string $character, string $value): array
    {
        return array_map(
            function ($item) use ($character) {
                return str_replace(__METHOD__, $character, $item);
            },
            explode($character, str_replace('\\'.$character, __METHOD__, $value))
        );
    }

    protected function arrayTablesContains(string $key): bool
    {
        return $this->arrayTables->contains($key);
    }

    /**
     * @throws TomlError
     */
    protected function addArrayTableNode(ArrayTableNode $arrayTableNode): void
    {
        $header = $this->makeKey($arrayTableNode->key);

        if (
            $this->keysContains($header) || $this->tablesContains($header) || $this->implicitTablesContains($header)
        ) {
            throw new TomlError('key duplication');
        }

        $key = $header;
        $index = 0;

        for ($i = $this->arrayTables->count() - 1; $i >= 0; $i--) {
            $arrayTable = $this->arrayTables[$i];
            $arrayTableHeader = $this->makeHeaderFromArrayTable($arrayTable);

            if ($arrayTableHeader === $header) {
                $index++;

                continue;
            }

            if ($this->startsWith($header, $arrayTableHeader)) {
                $key = $arrayTable.substr($header, strlen($arrayTableHeader));

                break;
            }
        }

        if ($index === 0 && ! $this->tables->filter(
            function ($table) use ($header) {
                return $this->startsWith((string) $table, $header);
            })->isEmpty()
        ) {
            throw new TomlError('key duplication');
        }

        if ($this->keysContains($key) || $this->tablesContains($key)) {
            throw new TomlError('key duplication');
        }

        $key .= ".[$index]";
        $this->arrayTables->add($key);
        $this->tables->add($key);
    }

    /**
     * Polyfill for str_starts_with()
     */
    private function startsWith(string $haystack, string $needle): bool
    {
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
