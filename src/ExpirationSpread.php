<?php
declare(strict_types=1);

namespace ErikBooij\CacheScheduler;

use Exception;

/**
 * @codeCoverageIgnore
 */
class ExpirationSpread
{
    /** @var int */
    private $spread;

    /**
     * @param int $spread
     */
    public function __construct(int $spread)
    {
        $this->spread = $spread;
    }

    /**
     * @return int
     */
    public function determineDeviation(): int
    {
        try {
            return random_int(-$this->spread, $this->spread);
        } catch (Exception $exception) {
            return 0;
        }
    }
}
