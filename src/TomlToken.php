<?php

namespace MAA\Toml;

/**
 * @internal
 */
final class TomlToken
{
    public const EOF = 'EOF';
    public const BARE = 'BARE';
    public const WHITESPACE = 'WHITESPACE';
    public const NEWLINE = 'NEWLINE';
    public const STRING = 'STRING';
    public const COMMENT = 'COMMENT';
    public const EQUALS = 'EQUALS';
    public const PERIOD = 'PERIOD';
    public const COMMA = 'COMMA';
    public const COLON = 'COLON';
    public const PLUS = 'PLUS';
    public const LEFT_CURLY_BRACKET = 'LEFT_CURLY_BRACKET';
    public const RIGHT_CURLY_BRACKET = 'RIGHT_CURLY_BRACKET';
    public const LEFT_SQUARE_BRACKET = 'LEFT_SQUARE_BRACKET';
    public const RIGHT_SQUARE_BRACKET = 'RIGHT_SQUARE_BRACKET';

    /** @var string */
    public $type;

    /** @var mixed */
    public $value;

    /** @var bool */
    public $isMultiline;

    /**
     * @param string $type
     * @param mixed $value
     * @param bool $isMultiline
     */
    public function __construct(
        string $type,
        $value = null,
        bool $isMultiline = false
    ) {
        $this->type = $type;
        $this->value = $value;
        $this->isMultiline = $isMultiline;
    }

    /**
     * Create a token from an array
     *
     * @param array $from
     * @return self
     */
    public static function fromArray(array $from): self
    {
        $type = $from['type'];
        $value = isset($from['value']) ? $from['value'] : null;
        $isMultiline = isset($from['isMultiline']) ? $from['isMultiline'] : false;

        return new self($type, $value, $isMultiline);
    }
}
