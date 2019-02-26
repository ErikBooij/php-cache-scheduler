<?php
declare(strict_types=1);

namespace ErikBooij\Tests\CacheScheduler;

use DateTimeImmutable;
use ErikBooij\CacheScheduler\SystemClock;
use PHPUnit\Framework\TestCase;

class SystemClockTest extends TestCase
{
    public function testCurrentDateTime()
    {
        $systemClock = new SystemClock();

        $this->assertInstanceOf(DateTimeImmutable::class, $systemClock->currentDateTime());
    }
}
