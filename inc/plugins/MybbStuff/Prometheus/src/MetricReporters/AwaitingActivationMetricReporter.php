<?php
declare(strict_types=1);

namespace MybbStuff\Prometheus\MetricReporters;

use MybbStuff\Prometheus\Metric;

class AwaitingActivationMetricReporter extends CacheBasedMetricReporter
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'awaiting_activation';
    }

    /**
     * @inheritDoc
     */
    function getMetrics(): array
    {
        $metrics = [];

        $awaitingActivationCache = $this->readCache($this->cache, 'awaitingactivation');

        if (isset($awaitingActivationCache['users'])) {
            $metric = (new Metric('mybb_awaiting_activation_users', Metric::TYPE_GAUGE))
                ->setHelp('The number of users awaiting activation')
                ->setValue((int) $awaitingActivationCache['users']);

            if (isset($awaitingActivationCache['time'])) {
                $metric->setTimeStamp((int) $awaitingActivationCache['time']);
            }

            $metrics['mybb_awaiting_activation_users'] = $metric;
        }

        return $metrics;
    }
}