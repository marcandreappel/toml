<?php

namespace MAA\Toml;

use ArrayObject;

class TomlObject extends ArrayObject
{
    /**
     * @param array|object $array
     */
    public function __construct($array = [])
    {
        parent::__construct($array, ArrayObject::ARRAY_AS_PROPS);
    }
}
