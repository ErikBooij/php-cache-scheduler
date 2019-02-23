<?php
declare(strict_types=1);

namespace ErikBooij\CacheScheduler;

use DateTimeInterface;

class Schedule
{
    /** @var string */
    public const STATE_STALE = 'stale';

    /** @var string */
    public const STATE_UP_TO_DATE = 'up-to-date';

    /** @var int */
    public const MON = 1;

    /** @var int */
    public const TUE = 2;

    /** @var int */
    public const WED = 3;

    /** @var int */
    public const THU = 4;

    /** @var int */
    public const FRI = 5;

    /** @var int */
    public const SAT = 6;

    /** @var int */
    public const SUN = 7;

    /** @var SwitchOverPoint[] */
    private $switchOverPoints = [];

    /**
     * @param int $dayOfTheWeek
     * @param int $hour
     * @param int $minute
     *
     * @return self
     */
    public function allowStaleDataFrom(int $dayOfTheWeek, int $hour, int $minute = 0): self
    {
        $this->switchOverPoints[] = new SwitchOverPoint($dayOfTheWeek, $hour, $minute, self::STATE_STALE);

        return $this;
    }

    /**
     * @param DateTimeInterface $dateTime
     *
     * @return SwitchOverPoint
     */
    public function findNextUpToDateSwitchOverPoint(DateTimeInterface $dateTime): SwitchOverPoint
    {
        /** @var SwitchOverPoint[] $switchOverPoints */
        $switchOverPoints = $this->orderSwitchOverPoints();

        $hasPassedSwitchOverPoint = false;

        foreach ($switchOverPoints as $switchOverPoint) {
            if ($switchOverPoint->precedesOrMatchesDateTime($dateTime)) {
                $hasPassedSwitchOverPoint = true;
            }

            if (
                $hasPassedSwitchOverPoint &&
                $switchOverPoint->getState() === self::STATE_UP_TO_DATE &&
                $switchOverPoint->precedesOrMatchesDateTime($dateTime) === false
            ) {
                return $switchOverPoint;
            }
        }

        return $switchOverPoints[0];
    }

    /**
     * @param DateTimeInterface $dateTime
     *
     * @return string
     */
    public function getDesiredState(DateTimeInterface $dateTime): string
    {
        /** @var SwitchOverPoint[] $switchOverPoints */
        $switchOverPoints = $this->orderSwitchOverPoints();

        $state = '';

        foreach ($switchOverPoints as $switchOverPoint) {
            if ($switchOverPoint->precedesOrMatchesDateTime($dateTime)) {
                $state = $switchOverPoint->getState();
            } else if (!empty($state)) {
                return $state;
            }
        }

        // If none of the switch over points preceded or matched the current DateTime
        // that means the current DateTime was between MON 00:00:00 and the first switch over.
        // The state of the *last* switch over point is the current state.
        return $switchOverPoints[count($switchOverPoints) - 1]->getState();
    }

    /**
     * @return bool
     */
    public function isClear(): bool
    {
        return empty($this->switchOverPoints);
    }

    /**
     * @param int $dayOfTheWeek
     * @param int $hour
     * @param int $minute
     *
     * @return Schedule
     */
    public function requireUpToDateDataFrom(int $dayOfTheWeek, int $hour, int $minute = 0): self
    {
        $this->switchOverPoints[] = new SwitchOverPoint($dayOfTheWeek, $hour, $minute, self::STATE_UP_TO_DATE);

        return $this;
    }

    /**
     * @return SwitchOverPoint[]
     */
    private function orderSwitchOverPoints(): array
    {
        uasort($this->switchOverPoints, [$this, 'compare']);

        /** @var SwitchOverPoint[] */
        return $this->switchOverPoints;
    }

    /**
     * @param SwitchOverPoint $a
     * @param SwitchOverPoint $b
     *
     * @return int
     */
    private function compare(SwitchOverPoint $a, SwitchOverPoint $b): int
    {
        return
            $a->getDayOfTheWeek() <=> $b->getDayOfTheWeek() ?:
            $a->getHour() <=> $b->getHour() ?:
            $a->getMinute() <=> $b->getMinute();
    }
}
