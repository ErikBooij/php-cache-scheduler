<?php
declare(strict_types=1);

namespace ErikBooij\CacheScheduler\Exception;

use RuntimeException;

class EmptyScheduleException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('No switch over points to allow stale data or require up-to-date data have been defined');
    }
}
