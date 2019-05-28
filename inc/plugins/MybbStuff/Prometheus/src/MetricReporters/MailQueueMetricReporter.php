<?php
declare(strict_types=1);

namespace MybbStuff\Prometheus\MetricReporters;

use MybbStuff\Prometheus\Metric;

class MailQueueMetricReporter extends CacheBasedMetricReporter
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'mail_queue';
    }

    /**
     * @inheritDoc
     */
    function getMetrics(): array
    {
        $metrics = [];

        $mailQueueCache = $this->readCache($this->cache, 'mailqueue');

        if (isset($mailQueueCache['queue_size'])) {
            $metric = (new Metric('mybb_mail_queue_size', Metric::TYPE_GAUGE))
	            ->setHelp('The number of messages waiting in the mail queue.')
                ->setValue((int) $mailQueueCache['queue_size']);

            if (isset($mailQueueCache['last_run'])) {
                $metric->setTimeStamp((int) $mailQueueCache['last_run']);
            }

            $metrics['mybb_mail_queue_size'] = $metric;
        }

        return $metrics;
    }
}