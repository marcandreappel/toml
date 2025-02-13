<?php

namespace MAA\Toml;

use DateTimeInterface;
use stdClass;

/**
 * @internal
 */
class TomlDecoder
{
    /**
     * @throws TomlError
     * @return array|stdClass
     */
    public static function decode(string $input, bool $asArray = false, bool $asFloat = false)
    {
        $parser = new TomlParser($input, $asFloat);

        if ($asArray) {
            return self::toArray(TomlNormalizer::normalize($parser->parse()));
        }

        return self::toObject(TomlNormalizer::normalize($parser->parse()));
    }

    protected static function toArray($object)
    {
        if ($object instanceof DateTimeInterface) {
            return $object;
        }

        if ($object instanceof TomlInternalDateTime) {
            return $object;
        }

        if (is_array($object) || is_object($object)) {
            return array_map(function ($value) {
                return self::toArray($value);
            }, (array) $object);
        }

        return $object;
    }

    /**
     * @param array|TomlObject
     * @return array|object
     */
    protected static function toObject($arrayObject)
    {
        $return = [];

        foreach ($arrayObject as $key => $value) {
            $return[$key] = $value instanceof TomlObject || is_array($value) ? self::toObject($value) : $value;
        }

        return is_array($arrayObject) ? $return : (object) $return;
    }
}
