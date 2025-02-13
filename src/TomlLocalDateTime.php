<?php

namespace MAA\Toml;

final class TomlLocalDateTime extends TomlInternalDateTime
{
    /** @var int */
    public $year;

    /** @var int */
    public $month;

    /** @var int */
    public $day;

    /** @var int */
    public $hour;

    /** @var int */
    public $minute;

    /** @var int */
    public $second;

    /** @var int */
    public $millisecond;
    /**
     * @param int $year
     * @param int $month
     * @param int $day
     * @param int $hour
     * @param int $minute
     * @param int $second
     * @param int $millisecond
     */
    public function __construct(
        int $year,
        int $month,
        int $day,
        int $hour,
        int $minute,
        int $second,
        int $millisecond
    ) {
        $this->year = $year;
        $this->month = $month;
        $this->day = $day;
        $this->hour = $hour;
        $this->minute = $minute;
        $this->second = $second;
        $this->millisecond = $millisecond;
    }

    /**
     * @param mixed $value
     * @throws TomlError
     * @return self
     */
    public static function fromString($value): self
    {
        $components = preg_split('/[tT ]/', (string) $value);

        if (count($components) !== 2) {
            throw new TomlError("invalid local date-time format \"$value\"");
        }

        $date = TomlLocalDate::fromString($components[0]);
        $time = TomlLocalTime::fromString($components[1]);

        return new self(
            $date->year,
            $date->month,
            $date->day,
            $time->hour,
            $time->minute,
            $time->second,
            $time->millisecond
        );
    }

    public function __toString(): string
    {
        return "{$this->toDateString()}T{$this->toTimeString()}{$this->millisecondToString()}";
    }

    private function toDateString(): string
    {
        return "{$this->zeroPad($this->year, 4)}-{$this->zeroPad($this->month)}-{$this->zeroPad($this->day)}";
    }

    private function toTimeString(): string
    {
        return "{$this->zeroPad($this->hour)}:{$this->zeroPad($this->minute)}:{$this->zeroPad($this->second)}";
    }

    private function millisecondToString(): string
    {
        if ($this->millisecond === 0) {
            return '';
        }

        return ".$this->millisecond";
    }
}
