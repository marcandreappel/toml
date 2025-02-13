<?php

namespace MAA\Toml;

use stdClass;

class Toml
{
    /**
     * @param array|stdClass $data
     * @throws TomlError
     */
    public static function encode($data): string
    {
        return TomlEncoder::encode($data);
    }

    /**
     * @throws TomlError
     * @return array|stdClass
     */
    public static function decode(string $data, bool $asArray = false, bool $asFloat = false)
    {
        return TomlDecoder::decode($data, $asArray, $asFloat);
    }
}
