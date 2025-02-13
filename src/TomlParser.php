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
use MAA\Toml\Nodes\NumericNode;
use MAA\Toml\Nodes\OffsetDateTimeNode;
use MAA\Toml\Nodes\RootTableNode;
use MAA\Toml\Nodes\StringNode;
use MAA\Toml\Nodes\TableNode;
use MAA\Toml\Nodes\TomlDateTimeNode;
use MAA\Toml\Nodes\ValuableNode;
use Throwable;

/**
 * @internal
 */
final class TomlParser
{
    /** @var TomlTokenizer */
    protected $tokenizer;

    /** @var TomlKeystore */
    protected $keystore;

    /** @var RootTableNode */
    protected $rootTableNode;

    /** @var TableNode|RootTableNode */
    protected $tableNode;

    /** @var bool */
    private $asFloat;

    /**
     * @param string $input
     * @param bool $asFloat
     * @throws TomlError
     */
    public function __construct(string $input, bool $asFloat = false)
    {
        $this->tokenizer = new TomlTokenizer($input);
        $this->keystore = new TomlKeystore;
        $this->rootTableNode = new RootTableNode([]);
        $this->tableNode = $this->rootTableNode;
        $this->asFloat = $asFloat;
    }

    /**
     * @throws TomlError
     */
    /**
     * Parse the TOML input
     *
     * @return RootTableNode
     * @throws TomlError
     */
    public function parse(): RootTableNode
    {
        try {
            while (true) {
                $node = $this->expression();
                if (! $node) {
                    break;
                }

                $this->tokenizer->take(TomlToken::WHITESPACE);
                $this->tokenizer->take(TomlToken::COMMENT);
                $this->tokenizer->assert(TomlToken::NEWLINE, TomlToken::EOF);
                $this->keystore->addNode($node);
                if (in_array(get_class($node), [TableNode::class, ArrayTableNode::class])) {
                    $this->tableNode = $node;
                    $this->rootTableNode->addElement($node);
                } else {
                    $this->tableNode->addElement($node);
                }
            }
        } catch (TomlError $error) {
            throw new TomlError(
                $error->getMessage(),
                $this->tokenizer->getInput(),
                $this->tokenizer->getPosition()    // Removed trailing comma
            );
        }

        return $this->rootTableNode;
    }

    /**
     * @throws TomlError
     */
    /**
     * Parse an expression
     *
     * @return KeyValuePairNode|TableNode|ArrayTableNode|null
     * @throws TomlError
     */
    protected function expression()
    {
        $this->takeCommentsAndNewlines();
        $token = $this->tokenizer->peek();

        switch ($token->type) {
            case TomlToken::LEFT_SQUARE_BRACKET:
                return $this->table();
            case TomlToken::EOF:
                return null;
            default:
                return $this->keyValuePair();
        }
    }

    /**
     * @throws TomlError
     */
    protected function takeCommentsAndNewlines(): void
    {
        while (true) {
            $this->tokenizer->take(TomlToken::WHITESPACE);
            if ($this->tokenizer->take(TomlToken::COMMENT)) {
                if ($this->tokenizer->isEOF()) {
                    break;
                }
                $this->tokenizer->assert(TomlToken::NEWLINE);

                continue;
            }
            if (! $this->tokenizer->take(TomlToken::NEWLINE)) {
                break;
            }
        }
    }

    /**
     * @throws TomlError
     */
    protected function table(): TableNode
    {
        $this->tokenizer->next();

        $isArrayTable = $this->tokenizer->take(TomlToken::LEFT_SQUARE_BRACKET);
        $key = $this->key();

        $this->tokenizer->assert(TomlToken::RIGHT_SQUARE_BRACKET);

        if ($isArrayTable) {
            $this->tokenizer->assert(TomlToken::RIGHT_SQUARE_BRACKET);
        }

        return $isArrayTable ? new ArrayTableNode($key, []) : new TableNode($key, []);
    }

    /**
     * @throws TomlError
     */
    protected function key(): KeyNode
    {
        $keyNode = new KeyNode([]);

        do {
            $this->tokenizer->take(TomlToken::WHITESPACE);
            $token = $this->tokenizer->next();

            switch ($token->type) {
                case TomlToken::BARE:
                    $keyNode->addKey(new BareNode($token->value));

                    break;

                case TomlToken::STRING:
                    if ($token->isMultiline) {
                        throw new TomlError('unexpected string value');
                    }

                    $keyNode->addKey(new StringNode($token->value));

                    break;

                default:
                    throw new TomlError('unexpected token type: '.$token->type);
            }

            $this->tokenizer->take(TomlToken::WHITESPACE);
        } while ($this->tokenizer->take(TomlToken::PERIOD));

        return $keyNode;
    }

    /**
     * @throws TomlError
     */
    protected function keyValuePair(): KeyValuePairNode
    {
        $key = $this->key();

        $this->tokenizer->assert(TomlToken::EQUALS);
        $this->tokenizer->take(TomlToken::WHITESPACE);

        return new KeyValuePairNode($key, $this->value());
    }

    /**
     * Parse a value node
     *
     * @return ValuableNode
     * @throws TomlError
     */
    protected function value(): ValuableNode
    {
        $token = $this->tokenizer->next();

        switch ($token->type) {
            case TomlToken::STRING:
                return new StringNode($token->value);
            case TomlToken::BARE:
                return $this->booleanOrNumberOrDateOrDateTimeOrTime($token->value);
            case TomlToken::PLUS:
                return $this->plus();
            case TomlToken::LEFT_SQUARE_BRACKET:
                return $this->array();
            case TomlToken::LEFT_CURLY_BRACKET:
                return $this->inlineTable();
            default:
                throw new TomlError('unexpected token type: ' . $token->type);
        }
    }

    /**
     * Parse a value that could be a boolean, number, date, datetime, or time
     *
     * @param string $value
     * @return BooleanNode|NumericNode|TomlDateTimeNode
     * @throws TomlError
     */
    protected function booleanOrNumberOrDateOrDateTimeOrTime(string $value)
    {
        if ($value === 'true' || $value === 'false') {
            return new BooleanNode($value === 'true');
        }

        $subValue = substr($value, 1);
        $lowerValue = strtolower($value);
        if (strpos($subValue, '-') !== false && strpos($lowerValue, 'e-') === false) {
            return $this->dateOrDateTime($value);
        }

        if ($this->tokenizer->peek()->type === TomlToken::COLON) {
            return $this->time($value);
        }

        return $this->number($value);
    }

    /**
     * Parse a date or datetime value
     *
     * @param string $value
     * @return OffsetDateTimeNode|LocalDateTimeNode|LocalDateNode
     * @throws TomlError
     */
    protected function dateOrDateTime(string $value)
    {
        $token = $this->tokenizer->peek();

        if ($token->type === TomlToken::WHITESPACE && $token->value === ' ') {
            $this->tokenizer->next();
            $token = $this->tokenizer->peek();

            if ($token->type !== TomlToken::BARE) {
                return new LocalDateNode(TomlLocalDate::fromString($value));
            }

            $this->tokenizer->next();
            $value .= 'T';
            $value .= $token->value;
        }

        if (strpos(strtolower($value), 't') === false) {
            return new LocalDateNode(TomlLocalDate::fromString($value));
        }

        $tokens = $this->tokenizer->sequence(
            TomlToken::COLON,
            TomlToken::BARE,
            TomlToken::COLON,
            TomlToken::BARE    // Removed trailing comma
        );
        $value .= implode('', array_map(function (TomlToken $token) {
            return $token->value;
        }, $tokens));

        $lastTokenValue = strtolower($tokens[count($tokens) - 1]->value);

        if ($this->endsWith($lastTokenValue, 'z')) {
            return new OffsetDateTimeNode($this->parseDate($value));
        }

        if (strpos($lastTokenValue, '-') !== false) {
            $this->tokenizer->assert(TomlToken::COLON);
            $token = $this->tokenizer->expect(TomlToken::BARE);
            $value .= ':';
            $value .= $token->value;

            return new OffsetDateTimeNode($this->parseDate($value));
        }

        $tokenType = $this->tokenizer->peek()->type;

        if ($tokenType === TomlToken::PLUS) {
            $this->tokenizer->next();
            $tokens = $this->tokenizer->sequence(
                TomlToken::BARE,
                TomlToken::COLON,
                TomlToken::BARE
            );
            $value .= '+';
            $value .= implode('', array_map(function (TomlToken $token) {
                return $token->value;
            }, $tokens));

            return new OffsetDateTimeNode($this->parseDate($value));
        }

        if ($tokenType === TomlToken::PERIOD) {
            $this->tokenizer->next();
            $token = $this->tokenizer->expect(TomlToken::BARE);
            $value .= '.';
            $value .= $token->value;

            $tokenValue = (string) $token->value;
            if ($this->endsWith($tokenValue, 'Z')) {
                return new OffsetDateTimeNode($this->parseDate($value));
            }

            if (strpos($tokenValue, '-') !== false) {
                $this->tokenizer->assert(TomlToken::COLON);
                $token = $this->tokenizer->expect(TomlToken::BARE);
                $value .= ':';
                $value .= $token->value;

                return new OffsetDateTimeNode($this->parseDate($value));
            }

            if ($this->tokenizer->take(TomlToken::PLUS)) {
                $tokens = $this->tokenizer->sequence(
                    TomlToken::BARE,
                    TomlToken::COLON,
                    TomlToken::BARE
                );
                $value .= '+';
                $value .= implode('', array_map(function (TomlToken $token) {
                    return $token->value;
                }, $tokens));

                return new OffsetDateTimeNode($this->parseDate($value));
            }
        }

        return new LocalDateTimeNode(TomlLocalDateTime::fromString($value));
    }

    /**
     * Helper method to check if a string ends with a given substring
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    private function endsWith(string $haystack, string $needle): bool
    {
        $length = strlen($needle);
        if ($length === 0) {
            return true;
        }
        return substr($haystack, -$length) === $needle;
    }

    /**
     * @throws TomlError
     */
    protected function parseDate($value): TomlDateTime
    {
        try {
            return new TomlDateTime($value);
        } catch (Throwable) {
            throw new TomlError('error during datetime parsing');
        }
    }

    /**
     * Parse a time value
     *
     * @param string $value
     * @return LocalTimeNode
     * @throws TomlError
     */
    protected function time($value): LocalTimeNode
    {
        $tokens = $this->tokenizer->sequence(
            TomlToken::COLON,
            TomlToken::BARE,
            TomlToken::COLON,
            TomlToken::BARE    // Removed trailing comma
        );
        $value .= implode('', array_map(function (TomlToken $token) {
            return $token->value;
        }, $tokens));

        if ($this->tokenizer->take(TomlToken::PERIOD)) {
            $token = $this->tokenizer->expect(TomlToken::BARE);
            $value .= '.';
            $value .= $token->value;
        }

        return new LocalTimeNode(TomlLocalTime::fromString($value));
    }

    /**
     * Parse a number into either an integer or float node
     *
     * @param string|mixed $value
     * @return IntegerNode|FloatNode
     * @throws TomlError
     */
    protected function number($value)
    {
        // Handle special float values
        switch ($value) {
            case 'inf':
            case '+inf':
                return new FloatNode(INF);
            case '-inf':
                return new FloatNode(-INF);
            case 'nan':
            case '+nan':
            case '-nan':
                return new FloatNode(NAN);
        }

        // Convert to string once for multiple uses
        $stringValue = (string) $value;
        $lowerValue = strtolower($stringValue);

        // Handle numeric values
        if (strpos($stringValue, '0x') === 0) {
            return $this->integer($value, 16);
        }
        if (strpos($stringValue, '0o') === 0) {
            return $this->integer($value, 8);
        }
        if (strpos($stringValue, '0b') === 0) {
            return $this->integer($value, 2);
        }
        if (strpos($lowerValue, 'e') !== false) {
            return $this->float($value);
        }
        if ($this->tokenizer->peek()->type === TomlToken::PERIOD) {
            return $this->float($value);
        }

        return $this->integer($value, 10);
    }

    /**
     * @throws TomlError
     */
    protected function integer($value, $radix): IntegerNode
    {
        $isSignAllowed = $radix === 10;
        $areLeadingZerosAllowed = $radix !== 10;
        $int = $this->parseInteger($value, $isSignAllowed, $areLeadingZerosAllowed, false, $radix)['int'];

        return new IntegerNode($int);
    }

    /**
     * Parse an integer value with various formatting rules
     *
     * @param string|mixed $value The value to parse
     * @param bool $isSignAllowed Whether +/- signs are allowed
     * @param bool $areLeadingZerosAllowed Whether leading zeros are allowed
     * @param bool $isUnparsedAllowed Whether unparsed remains are allowed
     * @param int $radix The number base (2, 8, 10, or 16)
     * @param bool $asString Whether to return the number as a string
     * @return array{int: mixed, unparsed: string, sign: string}
     * @throws TomlError
     */
    protected function parseInteger(
        $value,
        $isSignAllowed,
        $areLeadingZerosAllowed,
        $isUnparsedAllowed,
        $radix,
        bool $asString = false
    ): array {
        if (preg_match('/[^0-9-+._oxabcdef]/i', (string) $value)) {
            throw new TomlError('unexpected non-numeric value');
        }

        $sign = '';
        $i = 0;
        if ($value[$i] === '+' || $value[$i] === '-') {
            if (! $isSignAllowed) {
                throw new TomlError('unexpected sign (+/-)');
            }
            $sign = $value[$i];
            $i++;
        }

        if (! $areLeadingZerosAllowed && $value[$i] === '0' && ($i + 1) !== strlen((string) $value)) {
            throw new TomlError('unexpected leading zero');
        }

        if (preg_match('/[+-]?0[obx](_|$)/im', (string) $value)) {
            throw new TomlError('unexpected number formatting');
        }

        $stringValue = (string) $value;
        if (strpos($stringValue, '0x') === 0 && ! preg_match('/^0[xX][0-9a-fA-F_]+$/', $stringValue)) {
            throw new TomlError('unexpected binary number formatting');
        }

        $isUnderscoreAllowed = false;
        $valueLength = strlen($stringValue);
        for (; $i < $valueLength; $i++) {
            $char = $value[$i];
            if ($char === '_') {
                if (! $isUnderscoreAllowed) {
                    throw new TomlError('unexpected underscore symbol');
                }
                $isUnderscoreAllowed = false;

                continue;
            }

            $octalOrBinary = ($radix === 8 && $char === 'o') || ($radix === 2 && $char === 'b');
            if (! ($i === 1 && $octalOrBinary) && ! $this->digitalChecks($radix, $char)) {
                break;
            }

            $isUnderscoreAllowed = true;
        }

        if (! $isUnderscoreAllowed) {
            throw new TomlError('unexpected underscore symbol');
        }

        $int = str_replace('_', '', TomlUtils::stringSlice($value, 0, $i));
        $unparsed = TomlUtils::stringSlice($value, $i);

        if (! $isUnparsedAllowed && $unparsed !== '') {
            throw new TomlError('unexpected unparsed part of numeric value');
        }

        $int = str_replace('0o', '0', $int);
        if (! $asString) {
            $int = intval($int, 0);
        }

        return [
            'int' => $int,
            'unparsed' => $unparsed,
            'sign' => $sign,
        ];
    }

    /**
     * @throws TomlError
     */
    protected function digitalChecks($radix, $value): bool
    {
        switch ($radix) {
            case 16:
                return TomlUtils::isHexadecimal($value);
            case 10:
                return TomlUtils::isDecimal($value);
            case 8:
                return TomlUtils::isOctal($value);
            case 2:
                return TomlUtils::isBinary($value);
            default:
                throw new TomlError('unexpected radix value');
        }
    }

    /**
     * Parse a float value
     *
     * @param string|mixed $value
     * @return FloatNode
     * @throws TomlError
     */
    protected function float($value): FloatNode
    {
        $parsed = $this->parseInteger($value, true, true, true, 10);
        $float = $parsed['int'];
        $unparsed = $parsed['unparsed'];
        $sign = $parsed['sign'];

        if ($this->tokenizer->take(TomlToken::PERIOD)) {
            if (preg_match('/^[+-]?0\d+/im', (string) $value)) {
                throw new TomlError('unexpected float formatting');
            }

            if ($unparsed !== '') {
                throw new TomlError('unexpected unparsed part of float value');
            }

            $token = $this->tokenizer->expect(TomlToken::BARE);
            $result = $this->parseInteger($token->value, false, true, true, 10, true);
            if (strpos((string) $float, '+') !== 0 && strpos((string) $float, '-') !== 0) {
                $float = "$sign$float";
            }
            $float .= ".{$result['int']}";
            $unparsed = $result['unparsed'];
        }

        if ($unparsed === '') {
            return new FloatNode($this->asFloat ? (float) $float : $float);
        }

        $stringUnparsed = (string) $unparsed;
        if (strpos($stringUnparsed, 'e') !== 0 && strpos($stringUnparsed, 'E') !== 0) {
            throw new TomlError('unexpected unparsed part of float value');
        }

        $float .= 'e';

        if (strlen($stringUnparsed) !== 1) {
            $float .= $this->parseInteger(substr($stringUnparsed, 1), true, true, false, 10)['int'];

            return new FloatNode((float) $float);
        }

        $this->tokenizer->assert(TomlToken::PLUS);
        $token = $this->tokenizer->expect(TomlToken::BARE);
        $float .= '+';
        $float .= $this->parseInteger($token->value, false, true, false, 10)['int'];

        return new FloatNode((float) $float);
    }

    /**
     * @throws TomlError
     */
    protected function plus(): FloatNode|IntegerNode
    {
        $token = $this->tokenizer->expect(TomlToken::BARE);

        return $this->number("+$token->value");
    }

    /**
     * @throws TomlError
     */
    protected function array(): ArrayNode
    {
        $arrayNode = new ArrayNode([]);

        while (true) {
            $this->takeCommentsAndNewlines();

            if ($this->tokenizer->peek()->type === TomlToken::RIGHT_SQUARE_BRACKET) {
                break;
            }

            $value = $this->value();
            $arrayNode->addElement($value);
            $this->takeCommentsAndNewlines();

            if (! $this->tokenizer->take(TomlToken::COMMA)) {
                $this->takeCommentsAndNewlines();

                break;
            }
        }

        $this->tokenizer->assert(TomlToken::RIGHT_SQUARE_BRACKET);

        return $arrayNode;
    }

    /**
     * @throws TomlError
     */
    protected function inlineTable(): InlineTableNode
    {
        $this->tokenizer->take(TomlToken::WHITESPACE);
        $inlineTableNode = new InlineTableNode([]);

        if ($this->tokenizer->take(TomlToken::RIGHT_CURLY_BRACKET)) {
            return $inlineTableNode;
        }

        $keystore = new TomlKeystore;
        while (true) {
            $keyValue = $this->keyValuePair();
            $keystore->addNode($keyValue);
            $inlineTableNode->addElement($keyValue);
            $this->tokenizer->take(TomlToken::WHITESPACE);
            if ($this->tokenizer->take(TomlToken::RIGHT_CURLY_BRACKET)) {
                break;
            }
            $this->tokenizer->assert(TomlToken::COMMA);
        }

        return $inlineTableNode;
    }
}
