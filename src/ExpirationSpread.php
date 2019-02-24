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
    private function __construct(int $spread)
    {
        $this->spread = abs($spread);
    }

    /**
     * @param int $seconds
     *
     * @return ExpirationSpread
     */
    public static function seconds(int $seconds): self
    {
        return new static($seconds);
    }

    /**
     * @param int $minutes
     *
     * @return ExpirationSpread
     */
    public static function minutes(int $minutes): self
    {
        return new static($minutes * 60);
    }

    /**
     * @param int $hours
     *
     * @return ExpirationSpread
     */
    public static function hours(int $hours): self
    {
        return new static($hours * 3600);
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
