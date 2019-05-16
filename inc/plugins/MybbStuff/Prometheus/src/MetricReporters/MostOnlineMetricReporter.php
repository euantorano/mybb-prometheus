<?php
declare(strict_types=1);

namespace MybbStuff\Prometheus\MetricReporters;

use MybbStuff\Prometheus\Metric;

class MostOnlineMetricReporter extends CacheBasedMetricReporter
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'most_online';
    }

    /**
     * @inheritDoc
     */
    function getMetrics(): array
    {
        $metrics = [];

        $mostOnlineCache = $this->readCache($this->cache, 'mostonline');

        if (isset($mostOnlineCache['numusers'])) {
            $metric = (new Metric('mybb_most_online', Metric::TYPE_GAUGE))
                ->setHelp('The maximum number of users that have been online concurrently')
                ->setValue((int) $mostOnlineCache['numusers']);

            if (isset($mostOnlineCache['time'])) {
                $metric->setTimeStamp((int) $mostOnlineCache['time']);
            }

            $metrics['mybb_most_online'] = $metric;
        }

        return $metrics;
    }
}