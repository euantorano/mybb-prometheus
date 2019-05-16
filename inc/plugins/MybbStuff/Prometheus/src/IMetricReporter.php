<?php
declare(strict_types=1);

namespace MybbStuff\Prometheus;

interface IMetricReporter
{
    /**
     * Get the name of the metric reporter.
     *
     * @return string
     */
    function getName(): string;

    /**
     * Get all of the metrics for this reporter.
     *
     * @return Metric[]
     */
    function getMetrics(): array;
}