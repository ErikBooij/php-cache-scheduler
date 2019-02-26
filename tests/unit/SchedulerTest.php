<?php
declare(strict_types=1);

namespace ErikBooij\Tests\CacheScheduler;

use DateTimeImmutable;
use DateTimeInterface;
use ErikBooij\CacheScheduler\Exception\EmptyScheduleException;
use ErikBooij\CacheScheduler\Exception\NoScheduleProvidedException;
use ErikBooij\CacheScheduler\Exception\UnableToReadCurrentDateTimeException;
use ErikBooij\CacheScheduler\ExpirationSpread;
use ErikBooij\CacheScheduler\Schedule;
use ErikBooij\CacheScheduler\Scheduler;
use ErikBooij\CacheScheduler\SwitchOverPoint;
use ErikBooij\CacheScheduler\SystemClock;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

class SchedulerTest extends TestCase
{
    /** @var int */
    private const DEFAULT_TTL = 3600;

    /** @var ObjectProphecy */
    private $schedule;

    /** @var ObjectProphecy */
    private $systemClock;

    protected function setUp(): void
    {
        $this->schedule = $this->prophesize(Schedule::class);
        $this->systemClock = $this->prophesize(SystemClock::class);

        $this->schedule->isClear()->willReturn(false);
    }

    /**
     * @return void
     */
    public function testCalculateTimeToLiveShouldThrowExceptionWhenNoScheduleIsProvided(): void
    {
        $this->expectException(NoScheduleProvidedException::class);

        $scheduler = new Scheduler($this->systemClock->reveal());
        $scheduler->calculateTimeToLive(self::DEFAULT_TTL);
    }

    /**
     * @return void
     */
    public function testCalculateTimeToLiveShouldThrowExceptionWhenPassedAnEmptySchedule(): void
    {
        $this->expectException(EmptyScheduleException::class);
        $this->schedule->isClear()->willReturn(true);

        $scheduler = (new Scheduler($this->systemClock->reveal()))
            ->setSchedule($this->schedule->reveal());

        $scheduler->calculateTimeToLive(self::DEFAULT_TTL);
    }

    /**
     * @return void
     */
    public function testCalculateTimeToLiveShouldReturnDefaultTTLWhenUnableToReadCurrentDateTime(): void
    {
        $this->systemClock->currentDateTime()->willThrow(new UnableToReadCurrentDateTimeException);

        $scheduler = (new Scheduler($this->systemClock->reveal()))
            ->setSchedule($this->schedule->reveal());

        $this->assertEquals(self::DEFAULT_TTL, $scheduler->calculateTimeToLive(self::DEFAULT_TTL));
    }

    /**
     * @return void
     */
    public function testCalculateTimeToLiveShouldReturnDefaultTTLWhenDesiredStateIsUpToDate(): void
    {
        $this->schedule->getDesiredState(Argument::type(DateTimeInterface::class))->willReturn(Schedule::STATE_UP_TO_DATE);
        $this->systemClock->currentDateTime()->willReturn(new DateTimeImmutable('2019-02-20 12:00:00'));

        $scheduler = (new Scheduler($this->systemClock->reveal()))
            ->setSchedule($this->schedule->reveal());

        $this->assertEquals(self::DEFAULT_TTL, $scheduler->calculateTimeToLive(self::DEFAULT_TTL));
    }

    /**
     * @dataProvider calculateStaleTimeToLiveDataProvider
     *
     * @param DateTimeInterface $currentDateTime
     * @param int               $switchOverDayOfTheWeek
     * @param int               $switchOverHour
     * @param int               $switchOverMinute
     * @param int               $expectedTimeToLive
     *
     * @return void
     */
    public function testCalculateTimeToLiveShouldReturnSecondsToNextSwitchOverPointWhenDesiredStateIsStale(
        DateTimeInterface $currentDateTime,
        int $switchOverDayOfTheWeek,
        int $switchOverHour,
        int $switchOverMinute,
        int $expectedTimeToLive
    ): void {
        $switchOverPoint = $this->prophesize(SwitchOverPoint::class);
        $switchOverPoint->getDayOfTheWeek()->willReturn($switchOverDayOfTheWeek);
        $switchOverPoint->getHour()->willReturn($switchOverHour);
        $switchOverPoint->getMinute()->willReturn($switchOverMinute);

        $this->schedule->getDesiredState(Argument::type(DateTimeInterface::class))->willReturn(Schedule::STATE_STALE);
        $this->schedule->findNextUpToDateSwitchOverPoint(Argument::type(DateTimeInterface::class))->willReturn($switchOverPoint);

        $this->systemClock->currentDateTime()->willReturn($currentDateTime);

        $scheduler = (new Scheduler($this->systemClock->reveal()))
            ->setSchedule($this->schedule->reveal());

        $this->assertEquals($expectedTimeToLive, $scheduler->calculateTimeToLive(self::DEFAULT_TTL));
    }

    /**
     * @return array[]
     */
    public function calculateStaleTimeToLiveDataProvider(): array
    {
        return [
            'Monday evening to tuesday morning' => [
                'current'                => new DateTimeImmutable('2019-02-18 20:00:00'),
                'switchOverDayOfTheWeek' => Schedule::TUE,
                'switchOverHour'         => 9,
                'switchOverMinute'       => 15,
                'expectedTimeToLive'     => 47700,
            ],
            'Tuesday morning to tuesday morning' => [
                'current'                => new DateTimeImmutable('2019-02-19 07:00:00'),
                'switchOverDayOfTheWeek' => Schedule::TUE,
                'switchOverHour'         => 9,
                'switchOverMinute'       => 15,
                'expectedTimeToLive'     => 8100,
            ],
            'Friday evening to monday morning' => [
                'current'                => new DateTimeImmutable('2019-02-22 20:00:00'),
                'switchOverDayOfTheWeek' => Schedule::MON,
                'switchOverHour'         => 9,
                'switchOverMinute'       => 15,
                'expectedTimeToLive'     => 220500,
            ],
            'Saturday afternoon to monday morning' => [
                'current'                => new DateTimeImmutable('2019-02-23 15:00:00'),
                'switchOverDayOfTheWeek' => Schedule::MON,
                'switchOverHour'         => 9,
                'switchOverMinute'       => 15,
                'expectedTimeToLive'     => 152100,
            ],
            'Midnight sunday to monday to monday morning' => [
                'current'                => new DateTimeImmutable('2019-02-18 00:00:00'),
                'switchOverDayOfTheWeek' => Schedule::MON,
                'switchOverHour'         => 9,
                'switchOverMinute'       => 15,
                'expectedTimeToLive'     => 33300,
            ],
        ];
    }

    /**
     * @return void
     */
    public function testCalculateTimeToLiveShouldReturnSecondsToNextSwitchOverPointFuzzedWithinSpecifiedBounds(): void {
        $expirationSpread = $this->prophesize(ExpirationSpread::class);
        $switchOverPoint = $this->prophesize(SwitchOverPoint::class);

        $expirationSpread->determineDeviation()->willReturn(-540);

        $switchOverPoint->getDayOfTheWeek()->willReturn(Schedule::MON);
        $switchOverPoint->getHour()->willReturn(8);
        $switchOverPoint->getMinute()->willReturn(0);

        $this->schedule->getDesiredState(Argument::type(DateTimeInterface::class))->willReturn(Schedule::STATE_STALE);
        $this->schedule->findNextUpToDateSwitchOverPoint(Argument::type(DateTimeInterface::class))->willReturn($switchOverPoint);

        $this->systemClock->currentDateTime()->willReturn(new DateTimeImmutable('2019-02-18 02:00:00'));

        $scheduler = (new Scheduler($this->systemClock->reveal()))
            ->setSchedule($this->schedule->reveal())
            ->setExpirationSpread($expirationSpread->reveal());

        $this->assertEquals(21060, $scheduler->calculateTimeToLive(self::DEFAULT_TTL), '', 1800.0);
    }
}
