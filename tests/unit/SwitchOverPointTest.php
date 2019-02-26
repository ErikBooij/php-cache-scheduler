<?php
declare(strict_types=1);

namespace ErikBooij\Tests\CacheScheduler;

use DateTimeImmutable;
use DateTimeInterface;
use ErikBooij\CacheScheduler\Schedule;
use ErikBooij\CacheScheduler\SwitchOverPoint;
use PHPUnit\Framework\TestCase;

class SwitchOverPointTest extends TestCase
{
    /**
     * @dataProvider precedesDateTimeDataProvider
     *
     * @param DateTimeInterface $dateTime
     * @param bool               $expectedPrecedes
     */
    public function testPrecedesOrMatchesDateTime(DateTimeInterface $dateTime, bool $expectedPrecedes): void
    {
        // For test week used in data provides this would match '2019-02-20 14:30:00'
        $switchOverPoint = new SwitchOverPoint(Schedule::WED, 14, 30, Schedule::STATE_STALE);

        $this->assertEquals($expectedPrecedes, $switchOverPoint->precedesOrMatchesDateTime($dateTime));
    }

    /**
     * @return array
     */
    public function precedesDateTimeDataProvider(): array
    {
        // Key signifies how dow, hour and minute compare to the switch over point
        // Since they can all be either <, === or > compare to the switch over point,
        // that results in a 3 x 3 x 3 test matrix of 27 options.
        // Key here is "{day-of-the-week} {hour} {minute}"
        return [
            '< < <' => [
                new DateTimeImmutable('2019-02-19 12:00:00'),
                false
            ],
            '= < <' => [
                new DateTimeImmutable('2019-02-20 12:00:00'),
                false
            ],
            '> < <' => [
                new DateTimeImmutable('2019-02-21 12:00:00'),
                true
            ],
            '< = <' => [
                new DateTimeImmutable('2019-02-19 14:00:00'),
                false
            ],
            '= = <' => [
                new DateTimeImmutable('2019-02-20 14:00:00'),
                false
            ],
            '> = <' => [
                new DateTimeImmutable('2019-02-21 14:00:00'),
                true
            ],
            '< > <' => [
                new DateTimeImmutable('2019-02-19 16:00:00'),
                false
            ],
            '= > <' => [
                new DateTimeImmutable('2019-02-20 16:00:00'),
                true
            ],
            '> > <' => [
                new DateTimeImmutable('2019-02-21 16:00:00'),
                true
            ],
            '< < =' => [
                new DateTimeImmutable('2019-02-19 12:30:00'),
                false
            ],
            '= < =' => [
                new DateTimeImmutable('2019-02-20 12:30:00'),
                false
            ],
            '> < =' => [
                new DateTimeImmutable('2019-02-21 12:30:00'),
                true
            ],
            '< = =' => [
                new DateTimeImmutable('2019-02-19 14:30:00'),
                false
            ],
            '= = =' => [
                new DateTimeImmutable('2019-02-20 14:30:00'),
                true
            ],
            '> = =' => [
                new DateTimeImmutable('2019-02-21 14:30:00'),
                true
            ],
            '< > =' => [
                new DateTimeImmutable('2019-02-19 16:30:00'),
                false
            ],
            '= > =' => [
                new DateTimeImmutable('2019-02-20 16:30:00'),
                true
            ],
            '> > =' => [
                new DateTimeImmutable('2019-02-21 16:30:00'),
                true
            ],
            '< < >' => [
                new DateTimeImmutable('2019-02-19 12:45:00'),
                false
            ],
            '= < >' => [
                new DateTimeImmutable('2019-02-20 12:45:00'),
                false
            ],
            '> < >' => [
                new DateTimeImmutable('2019-02-21 12:45:00'),
                true
            ],
            '< = >' => [
                new DateTimeImmutable('2019-02-19 14:45:00'),
                false
            ],
            '= = >' => [
                new DateTimeImmutable('2019-02-20 14:45:00'),
                true
            ],
            '> = >' => [
                new DateTimeImmutable('2019-02-21 14:45:00'),
                true
            ],
            '< > >' => [
                new DateTimeImmutable('2019-02-19 16:45:00'),
                false
            ],
            '= > >' => [
                new DateTimeImmutable('2019-02-20 16:45:00'),
                true
            ],
            '> > >' => [
                new DateTimeImmutable('2019-02-21 16:45:00'),
                true
            ],
        ];
    }

    public function testGetDayOfTheWeek()
    {
        $switchOverPoint = new SwitchOverPoint(Schedule::WED, 14, 30, Schedule::STATE_STALE);

        $this->assertEquals(3, $switchOverPoint->getDayOfTheWeek());
    }

    public function testGetHour()
    {
        $switchOverPoint = new SwitchOverPoint(Schedule::WED, 14, 30, Schedule::STATE_STALE);

        $this->assertEquals(14, $switchOverPoint->getHour());
    }

    public function testGetMinute()
    {
        $switchOverPoint = new SwitchOverPoint(Schedule::WED, 14, 30, Schedule::STATE_STALE);

        $this->assertEquals(30, $switchOverPoint->getMinute());
    }

    public function testGetState()
    {
        $switchOverPoint = new SwitchOverPoint(Schedule::WED, 14, 30, Schedule::STATE_STALE);

        $this->assertEquals('stale', $switchOverPoint->getState());
    }
}
