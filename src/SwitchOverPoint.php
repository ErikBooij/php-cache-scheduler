<?php
declare(strict_types=1);

namespace ErikBooij\CacheScheduler;

use DateTimeInterface;

class SwitchOverPoint
{
    /** @var int */
    private $dayOfTheWeek;

    /** @var int */
    private $hour;

    /** @var int */
    private $minute;

    /** @var string */
    private $state;

    /**
     * @param int    $dayOfTheWeek
     * @param int    $hour
     * @param int    $minute
     * @param string $state
     */
    public function __construct(int $dayOfTheWeek, int $hour, int $minute, string $state)
    {
        $this->dayOfTheWeek = $dayOfTheWeek;
        $this->hour = $hour;
        $this->minute = $minute;
        $this->state = $state;
    }

    /**
     * @return int
     */
    public function getDayOfTheWeek(): int
    {
        return $this->dayOfTheWeek;
    }

    /**
     * @return int
     */
    public function getHour(): int
    {
        return $this->hour;
    }

    /**
     * @return int
     */
    public function getMinute(): int
    {
        return $this->minute;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param DateTimeInterface $dateTime
     *
     * @return bool
     */
    public function precedesOrMatchesDateTime(DateTimeInterface $dateTime): bool
    {
        $dow = (int)$dateTime->format('N');

        if ($this->dayOfTheWeek < $dow) {
            return true;
        }

        $hour = (int)$dateTime->format('G');

        if ($this->dayOfTheWeek === $dow && $this->hour < $hour) {
            return true;
        }

        $minute = (int)$dateTime->format('i');

        if ($this->dayOfTheWeek === $dow && $this->hour === $hour && $this->minute <= $minute) {
            return true;
        }

        return false;
    }
}
