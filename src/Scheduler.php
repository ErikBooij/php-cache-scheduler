<?php
declare(strict_types=1);

namespace ErikBooij\CacheScheduler;

use DateTime;
use ErikBooij\CacheScheduler\Exception\EmptyScheduleException;
use ErikBooij\CacheScheduler\Exception\NoScheduleProvidedException;
use ErikBooij\CacheScheduler\Exception\UnableToReadCurrentDateTimeException;
use Exception;

class Scheduler
{
    /** @var ExpirationSpread|null */
    private $expirationSpread;

    /** @var Schedule|null */
    private $schedule;

    /** @var SystemClock */
    private $systemClock;

    /**
     * @param SystemClock      $systemClock
     * @param Schedule|null    $schedule
     * @param ExpirationSpread $expirationSpread
     */
    public function __construct(SystemClock $systemClock, Schedule $schedule = null, ExpirationSpread $expirationSpread = null)
    {
        $this->systemClock = $systemClock;
        $this->schedule = $schedule;
        $this->expirationSpread = $expirationSpread;
    }

    /**
     * @param int           $upToDateTTL
     * @param Schedule|null $schedule
     *
     * @return int
     * @throws NoScheduleProvidedException
     * @throws EmptyScheduleException
     */
    public function calculateTimeToLive(int $upToDateTTL, Schedule $schedule = null): int
    {
        $schedule = $schedule ?? $this->schedule;

        if ($schedule === null) {
            throw new NoScheduleProvidedException;
        }

        if ($schedule->isClear()) {
            throw new EmptyScheduleException;
        }

        try {
            $currentDateTime = $this->systemClock->currentDateTime();

            $desiredState = $schedule->getDesiredState($currentDateTime);

            if ($desiredState === Schedule::STATE_UP_TO_DATE) {
                return $upToDateTTL;
            }

            $switchOverPoint = $schedule->findNextUpToDateSwitchOverPoint($currentDateTime);

            $deviation = 0;

            if ($this->expirationSpread instanceof ExpirationSpread) {
                $deviation = $this->expirationSpread->determineDeviation();
            }

            return $this->secondsToSwitchOverPoint($switchOverPoint) + $deviation;
        } catch (Exception $ex) {
            return $upToDateTTL;
        }
    }

    /**
     * @param SwitchOverPoint $switchOverPoint
     *
     * @return int
     * @throws UnableToReadCurrentDateTimeException
     * @throws Exception
     */
    private function secondsToSwitchOverPoint(SwitchOverPoint $switchOverPoint): int
    {
        $daysOfTheWeek = [
            Schedule::MON => 'monday',
            Schedule::TUE => 'tuesday',
            Schedule::WED => 'wednesday',
            Schedule::THU => 'thursday',
            Schedule::FRI => 'friday',
            Schedule::SAT => 'saturday',
            Schedule::SUN => 'sunday',
        ];

        $currentDateTime = $this->systemClock->currentDateTime();

        // Create a mutable copy of the current DateTime and set date and time to that of the switch over point
        $switchOverDateTime = new DateTime($currentDateTime->format(DateTime::ATOM));

        if ((int)$switchOverDateTime->format('N') !== $switchOverPoint->getDayOfTheWeek()) {
            $switchOverDateTime->modify("next {$daysOfTheWeek[$switchOverPoint->getDayOfTheWeek()]}");
        }

        $switchOverDateTime->setTime($switchOverPoint->getHour(), $switchOverPoint->getMinute());

        return $switchOverDateTime->getTimestamp() - $currentDateTime->getTimestamp();
    }
}
