<?php
declare(strict_types=1);

namespace MybbStuff\Prometheus;

use InvalidArgumentException;

final class Metric
{
    public const TYPE_COUNTER = 'counter';

    public const TYPE_GAUGE = 'gauge';

    public const TYPE_HISTOGRAM = 'histogram';

    public const TYPE_SUMMARY = 'summary';

    public const TYPE_UNTYPED = 'untyped';

    private static $allowableTypes = [
        self::TYPE_COUNTER,
        self::TYPE_GAUGE,
        self::TYPE_HISTOGRAM,
        self::TYPE_SUMMARY,
        self::TYPE_UNTYPED,
    ];

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string|null
     */
    private $help;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @var int|null
     */
    private $timeStamp;

    /**
     * Create a new metric.
     *
     * @param string $name The name of the metric.
     * @param string $type The type of the metric.
     */
    public function __construct(string $name, string $type = self::TYPE_UNTYPED)
    {
        $this->setName($name)
            ->setType($type);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Metric
     *
     * @throws \InvalidArgumentException Thrown if {@see $name} is empty.
     */
    public function setName(string $name): self
    {
        if (empty($name)) {
            throw new InvalidArgumentException('Metric name is required');
        }

        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return Metric
     *
     * @throws \InvalidArgumentException Thrown if {@see $type} is an invalid type.
     */
    public function setType(string $type): self
    {
        if (!in_array($type, static::$allowableTypes)) {
            throw new InvalidArgumentException('Invalid metric type');
        }

        $this->type = $type;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getHelp(): ?string
    {
        return $this->help;
    }

    /**
     * @param string|null $help
     *
     * @return Metric
     */
    public function setHelp(?string $help): self
    {
        $this->help = $help;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     *
     * @return Metric
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getTimeStamp(): ?int
    {
        return $this->timeStamp;
    }

    /**
     * @param int|null $timeStamp
     *
     * @return Metric
     */
    public function setTimeStamp(?int $timeStamp): self
    {
        $this->timeStamp = $timeStamp;

        return $this;
    }

    /**
     * Write the metric to a string.
     *
     * @return string
     */
    public function render(): string
    {
        $str = '';

        if (!empty($this->help)) {
            $str .= "# HELP {$this->name} {$this->help}\n";
        }

        $str .= "# TYPE {$this->name} {$this->type}\n";

        $str .= "{$this->name}";

        if (!is_null($this->timeStamp)) {
            $str .= " {$this->timeStamp}";
        }

        $str .= "\n\n";

        return $str;
    }
}