<?php
declare(strict_types=1);

namespace MybbStuff\Prometheus\MetricReporters;

use datacache;
use MybbStuff\Prometheus\IMetricReporter;

abstract class CacheBasedMetricReporter implements IMetricReporter
{
    /**
     * @var \datacache
     */
    protected $cache;

    public function __construct(datacache $cache)
    {
        $this->cache = $cache;
    }

    protected function readCache(datacache $cache, string $name): array
    {
        $value = $cache->read($name);

        if ($value === false) {
            $value = [];
        }

        return $value;
    }
}