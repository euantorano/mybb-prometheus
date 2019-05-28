<?php
declare(strict_types=1);

namespace MybbStuff\Prometheus;

use ArrayAccess;
use Countable;

class MetricReporterRegistry implements ArrayAccess, Countable
{
    /**
     * @var \MybbStuff\Prometheus\MetricReporterRegistry
     */
    private static $instance;

    /**
     * @var array
     */
    private $metricReporters;

    private function __construct()
    {
        $this->metricReporters = [];
    }

    public static function getInstance(): self
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function addMetricReporter(IMetricReporter $metricReporter): self
    {
        $this->metricReporters[$metricReporter->getName()] = $metricReporter;

        return $this;
    }

    /**
     * @return Metric[]
     */
    public function getMetrics(): array
    {
        $metrics = [];

        foreach ($this->metricReporters as $metricReporter) {
            if (is_object($metricReporter) && $metricReporter instanceof IMetricReporter) {
                $metrics = array_merge($metrics, $metricReporter->getMetrics());
            }
        }

        return $metrics;
    }

    public function render(): string
    {
        $output = '';

        foreach ($this->getMetrics() as $metric) {
            $output .= $metric->render();
        }

        return $output;
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        return isset($this->metricReporters[$offset]);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return $this->metricReporters[$offset];
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        $this->metricReporters[$offset] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        unset($this->metricReporters[$offset]);
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        return count($this->metricReporters);
    }
}