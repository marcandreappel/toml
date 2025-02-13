<?php

namespace MAA\Toml;

use MAA\Toml\Nodes\ArrayNode;
use MAA\Toml\Nodes\ArrayTableNode;
use MAA\Toml\Nodes\BareNode;
use MAA\Toml\Nodes\BooleanNode;
use MAA\Toml\Nodes\FloatNode;
use MAA\Toml\Nodes\InlineTableNode;
use MAA\Toml\Nodes\IntegerNode;
use MAA\Toml\Nodes\KeyNode;
use MAA\Toml\Nodes\KeyValuePairNode;
use MAA\Toml\Nodes\LocalDateNode;
use MAA\Toml\Nodes\LocalDateTimeNode;
use MAA\Toml\Nodes\LocalTimeNode;
use MAA\Toml\Nodes\OffsetDateTimeNode;
use MAA\Toml\Nodes\RootTableNode;
use MAA\Toml\Nodes\StringNode;
use MAA\Toml\Nodes\TableNode;

/**
 * @internal
 */
final class TomlNormalizer
{
    /**
     * @param Nodes\Node $node
     * @return mixed
     * @throws TomlError
     */
    public static function normalize($node)
    {
        $nodeClass = get_class($node);
        switch ($nodeClass) {
            case InlineTableNode::class:
            case RootTableNode::class:
                $elements = self::mapNormalize($node->elements());

                return self::merge(...$elements);

            case KeyNode::class:
                return self::mapNormalize($node->keys());

            case KeyValuePairNode::class:
                $key = self::normalize($node->key);
                $value = self::normalize($node->value);

                return self::objectify($key, $value);

            case TableNode::class:
                $key = self::normalize($node->key);
                $elements = self::mapNormalize($node->elements());

                return self::objectify($key, self::merge(...$elements));

            case ArrayTableNode::class:
                $key = self::normalize($node->key);
                $elements = self::mapNormalize($node->elements());

                return self::objectify($key, [self::merge(...$elements)]);

            case ArrayNode::class:
                return self::mapNormalize($node->elements());

            case OffsetDateTimeNode::class:
            case LocalDateTimeNode::class:
            case LocalDateNode::class:
            case LocalTimeNode::class:
            case BareNode::class:
            case StringNode::class:
            case IntegerNode::class:
            case FloatNode::class:
            case BooleanNode::class:
                return $node->value;

            default:
                throw new TomlError('unsupported type: '.$nodeClass);
        }
    }

    /**
     * @param array $items
     * @return array
     * @throws TomlError
     */
    protected static function mapNormalize(array $items): array
    {
        return array_map(function ($element) {
            return self::normalize($element);
        }, $items);
    }

    /**
     * @param mixed ...$values
     * @return TomlObject
     * @throws TomlError
     */
    protected static function merge(...$values): TomlObject
    {
        return array_reduce($values, function (TomlObject $acc, $value) {
            foreach ($value as $key => $nextValue) {
                $prevValue = $acc->offsetExists($key) ? $acc->offsetGet($key) : null;

                if (is_array($prevValue) && is_array($nextValue)) {
                    $acc->{$key} = array_merge($prevValue, $nextValue);
                } elseif (self::isKeyValuePair($prevValue) && self::isKeyValuePair($nextValue)) {
                    $acc->{$key} = self::merge($prevValue, $nextValue);
                } elseif (is_array($prevValue) &&
                    self::isKeyValuePair(end($prevValue)) &&
                    self::isKeyValuePair($nextValue)) {
                    $prevValueLastElement = end($prevValue);
                    $acc->{$key} = array_merge(
                        array_slice($prevValue, 0, -1),
                        [self::merge($prevValueLastElement, $nextValue)]
                    );
                } elseif (isset($prevValue)) {
                    throw new TomlError('unexpected value');
                } else {
                    $acc->{$key} = $nextValue;
                }
            }

            return $acc;
        }, new TomlObject([]));
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected static function isKeyValuePair($value): bool
    {
        if ($value instanceof TomlInternalDateTime) {
            return false;
        }

        return is_object($value);
    }

    /**
     * @param string[] $keys
     * @param mixed $value
     * @return TomlObject
     */
    protected static function objectify(array $keys, $value): TomlObject
    {
        $initialValue = new TomlObject([]);
        $object = &$initialValue;
        foreach (array_slice($keys, 0, -1) as $prop) {
            $object->{$prop} = new TomlObject([]);
            $object = &$object->{$prop};
        }

        $key = array_pop($keys);
        $object->{$key} = $value;

        return $initialValue;
    }
}
