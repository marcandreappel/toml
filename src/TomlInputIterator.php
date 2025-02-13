<?php

namespace MAA\Toml;

/**
 * @internal
 */
final class TomlInputIterator
{
    public const EOF = '-1';

    /** @var int */
    public $pos = -1;

    /** @var int */
    private $inputLength;

    /** @var string */
    public $input;

    /**
     * @param string $input
     */
    public function __construct(string $input)
    {
        $this->input = $input;
        $this->inputLength = strlen($this->input);
    }

    public function take(...$chars): bool
    {
        $char = $this->peek();
        if ($char !== self::EOF && in_array($char, $chars, false)) {
            $this->next();

            return true;
        }

        return false;
    }

    public function peek(): string
    {
        $pos = $this->pos;
        $char = $this->next();
        $this->pos = $pos;

        return $char;
    }

    public function next(): string
    {
        if ($this->isEOF()) {
            return self::EOF;
        }

        $this->pos++;
        $char = $this->input[$this->pos];
        if ($char === "\r" && $this->input[$this->pos + 1] === "\n") {
            $this->pos++;

            return "\n";
        }

        return $char;
    }

    public function isEOF(): bool
    {
        return $this->pos + 1 === $this->inputLength;
    }
}
