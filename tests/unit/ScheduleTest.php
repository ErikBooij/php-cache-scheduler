<?php
declare(strict_types=1);

namespace ErikBooij\Tests\CacheScheduler;

use DateTimeImmutable;
use DateTimeInterface;
use ErikBooij\CacheScheduler\Schedule;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ErikBooij\CacheScheduler\Schedule
 */
class ScheduleTest extends TestCase
{
    /** @var Schedule */
    private $schedule;

    public function setUp(): void
    {
        $this->schedule = (new Schedule)
            ->requireUpToDateDataFrom(Schedule::MON, 8, 0)
            ->allowStaleDataFrom(Schedule::MON, 17, 30)
            ->requireUpToDateDataFrom(Schedule::TUE, 8, 0)
            ->allowStaleDataFrom(Schedule::TUE, 17, 30)
            ->requireUpToDateDataFrom(Schedule::WED, 8, 0)
            ->allowStaleDataFrom(Schedule::WED, 17, 30)
            ->requireUpToDateDataFrom(Schedule::THU, 8, 0)
            ->allowStaleDataFrom(Schedule::THU, 17, 30)
            ->requireUpToDateDataFrom(Schedule::FRI, 8, 0)
            ->allowStaleDataFrom(Schedule::FRI, 17, 30);
    }

    /**
     * @dataProvider desiredStateDataProvider
     *
     * @param DateTimeInterface $currentDateTime
     * @param string            $expectedState
     */
    public function testGetDesiredState(DateTimeInterface $currentDateTime, string $expectedState): void
    {
        $this->assertEquals($expectedState, $this->schedule->getDesiredState($currentDateTime));
    }

    /**
     * @return array[]
     */
    public function desiredStateDataProvider(): array
    {
        return [
            '1) Before first switch over point at midnight from stale to up-to-date' => [
                new DateTimeImmutable('2019-02-18 00:00:00'),
                Schedule::STATE_STALE,
            ],
            '2) Before first switch over point from stale to up-to-date'             => [
                new DateTimeImmutable('2019-02-18 05:00:00'),
                Schedule::STATE_STALE,
            ],
            '3) Just before first swith over point from stale to up-to-date'         => [
                new DateTimeImmutable('2019-02-18 07:59:00'),
                Schedule::STATE_STALE,
            ],
            '4) At exact time of first switch over point from stale to up-to-date'   => [
                new DateTimeImmutable('2019-02-18 08:00:00'),
                Schedule::STATE_UP_TO_DATE,
            ],
            '5) After switch over point from stale to up-to-date'                    => [
                new DateTimeImmutable('2019-02-18 08:30:00'),
                Schedule::STATE_UP_TO_DATE,
            ],
            '6) At exact time of switch over point from up-to-date to stale'         => [
                new DateTimeImmutable('2019-02-18 17:30:00'),
                Schedule::STATE_STALE,
            ],
            '7) Just after switch over point from up-to-date to stale'               => [
                new DateTimeImmutable('2019-02-18 17:31:00'),
                Schedule::STATE_STALE,
            ],
            '8) At midnight after first day in schedule'                             => [
                new DateTimeImmutable('2019-02-19 00:00:00'),
                Schedule::STATE_STALE,
            ],
            '9) Between last and first switch over point'                            => [
                new DateTimeImmutable('2019-02-22 19:00:00'),
                Schedule::STATE_STALE,
            ],
        ];
    }

    /**
     * @dataProvider findNextUpToDateSwitchOverPointDataProvider
     *
     * @param DateTimeInterface $currentDateTime
     * @param int               $expectedDayOfTheWeek
     * @param int               $expectedHour
     * @param int               $expectedMinute
     *
     * @return void
     */
    public function testFindNextUpToDateSwitchOverPoint(
        DateTimeInterface $currentDateTime,
        int $expectedDayOfTheWeek,
        int $expectedHour,
        int $expectedMinute
    ): void {
        $switchOverPoint = $this->schedule->findNextUpToDateSwitchOverPoint($currentDateTime);

        $this->assertEquals($expectedDayOfTheWeek, $switchOverPoint->getDayOfTheWeek());
        $this->assertEquals($expectedHour, $switchOverPoint->getHour());
        $this->assertEquals($expectedMinute, $switchOverPoint->getMinute());
    }

    /**
     * @return array[]
     */
    public function findNextUpToDateSwitchOverPointDataProvider(): array
    {
        return [
            'Monday before opening hours' => [
                'current'              => new DateTimeImmutable('2019-02-18 06:15:00'),
                'expectedDayOfTheWeek' => 1,
                'expectedHour'         => 8,
                'expectedMinute'       => 0,
            ],
            'Wednesday before opening hours' => [
                'current'              => new DateTimeImmutable('2019-02-19 06:24:00'),
                'expectedDayOfTheWeek' => 2,
                'expectedHour'         => 8,
                'expectedMinute'       => 0,
            ],
            'Wednesday during opening hours' => [
                'current'              => new DateTimeImmutable('2019-02-19 12:00:00'),
                'expectedDayOfTheWeek' => 3,
                'expectedHour'         => 8,
                'expectedMinute'       => 0,
            ],
            'Wednesday after opening hours' => [
                'current'              => new DateTimeImmutable('2019-02-19 20:00:00'),
                'expectedDayOfTheWeek' => 3,
                'expectedHour'         => 8,
                'expectedMinute'       => 0,
            ],
            'Friday after opening hours' => [
                'current'              => new DateTimeImmutable('2019-02-22 20:00:00'),
                'expectedDayOfTheWeek' => 1,
                'expectedHour'         => 8,
                'expectedMinute'       => 0,
            ],
            'Saturday morning' => [
                'current'              => new DateTimeImmutable('2019-02-23 11:00:00'),
                'expectedDayOfTheWeek' => 1,
                'expectedHour'         => 8,
                'expectedMinute'       => 0,
            ],
            'Sunday afternoon' => [
                'current'              => new DateTimeImmutable('2019-02-24 16:00:00'),
                'expectedDayOfTheWeek' => 1,
                'expectedHour'         => 8,
                'expectedMinute'       => 0,
            ],
        ];
    }
}
