<?php
declare(strict_types=1);

namespace MybbStuff\Prometheus\MetricReporters;

use MybbStuff\Prometheus\Metric;

class ReportedContentMetricReporter extends CacheBasedMetricReporter
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'reported_content';
    }

    /**
     * @inheritDoc
     */
    function getMetrics(): array
    {
        $metrics = [];

        $reportedContentCache = $this->readCache($this->cache, 'reportedcontent');

        if (isset($reportedContentCache['unread'])) {
            $metrics['mybb_reported_content_unread'] = (new Metric('mybb_reported_content_unread', Metric::TYPE_GAUGE))
                ->setHelp('The number of unread reports')
                ->setValue((int) $reportedContentCache['unread']);
        }

        if (isset($reportedContentCache['total'])) {
            $metrics['mybb_reported_content_total'] = (new Metric('mybb_reported_content_total', Metric::TYPE_GAUGE))
                ->setHelp('The total number of reports')
                ->setValue((int) $reportedContentCache['total']);
        }

        return $metrics;
    }
}