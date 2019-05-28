<?php
declare(strict_types=1);

namespace MybbStuff\Prometheus\MetricReporters;

use MybbStuff\Prometheus\Metric;

class VersionCodeMetricReporter extends CacheBasedMetricReporter
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'version_code';
    }

    /**
     * @inheritDoc
     */
    function getMetrics(): array
    {
        $metrics = [];

        $versionCache = $this->readCache($this->cache, 'version');

        if (isset($versionCache['version_code'])) {
            $metrics['mybb_version_code'] = (new Metric('mybb_version_code', Metric::TYPE_UNTYPED))
	            ->setHelp('The version code of the currently installed MyBB version.')
                ->setValue($versionCache['version_code']);
        }

        return $metrics;
    }
}