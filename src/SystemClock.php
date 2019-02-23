<?php
declare(strict_types=1);

namespace ErikBooij\CacheScheduler;

use DateTimeImmutable;
use DateTimeInterface;
use ErikBooij\CacheScheduler\Exception\UnableToReadCurrentDateTimeException;
use Exception;

class SystemClock
{
    /**
     * @return DateTimeInterface
     * @throws UnableToReadCurrentDateTimeException
     */
    public function currentDateTime(): DateTimeInterface
    {
        try {
            return new DateTimeImmutable;
        } catch (Exception $exception) {
            throw new UnableToReadCurrentDateTimeException;
        }
    }
}
