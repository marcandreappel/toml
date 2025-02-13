<?php

namespace MAA\Toml;

final class TomlLocalDate extends TomlInternalDateTime
{
    /** @var int */
    public $year;

    /** @var int */
    public $month;

    /** @var int */
    public $day;
    /**
     * @param int $year
     * @param int $month
     * @param int $day
     */
    public function __construct(
        int $year,
        int $month,
        int $day
    ) {
        $this->year = $year;
        $this->month = $month;
        $this->day = $day;
    }

    /**
     * @param mixed $value
     * @throws TomlError
     * @return self
     */
    public static function fromString($value): self
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value)) {
            throw new TomlError("invalid local date format \"$value\"");
        }

        [$year, $month, $day] = array_map('intval', explode('-', (string) $value));

        if (! self::isYear($year) || ! self::isMonth($month) || ! self::isDay($day)) {
            throw new TomlError("invalid local date format \"$value\"");
        }

        if (! self::isValidFebruary($year, $month, $day)) {
            throw new TomlError('invalid local date: days of February');
        }

        return new self($year, $month, $day);
    }

    public function __toString(): string
    {
        return "{$this->zeroPad($this->year, 4)}-{$this->zeroPad($this->month)}-{$this->zeroPad($this->day)}";
    }
}
