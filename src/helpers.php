<?php

use MAA\Toml\Toml;
use MAA\Toml\TomlError;

if (! function_exists('toml_encode')) {
    /**
     * @param array|stdClass $toml
     * @throws TomlError
     */
    function toml_encode($toml): string
    {
        return Toml::encode($toml);
    }
}

if (! function_exists('toml_decode')) {
    /**
     * @return array|stdClass
     * @throws TomlError
     */
    function toml_decode(string $toml, bool $asArray = false, bool $asFloat = false)
    {
        return Toml::decode($toml, $asArray, $asFloat);
    }
}
