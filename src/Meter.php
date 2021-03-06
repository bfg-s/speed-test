<?php

namespace Bfg\SpeedTest;

/**
 * Class Meter.
 * @package Bfg\SpeedTest
 */
class Meter
{
    /**
     * Number of launches.
     *
     * @var int
     */
    public int $times = 1;

    /**
     * Start microtime.
     *
     * @var int
     */
    public int $start = 0;

    /**
     * End microtime.
     *
     * @var int
     */
    public int $end = 0;

    /**
     * Start memory in bytes.
     *
     * @var int
     */
    public int $mem_start = 0;

    /**
     * End memory in bytes.
     *
     * @var int
     */
    public int $mem_end = 0;

    /**
     * Diff (End-Start) converted to seconds.
     *
     * @var float
     */
    public float $diff = 0;

    /**
     * Diff (MemEnd-MemStart) in bytes.
     *
     * @var float
     */
    public float $mem_diff = 0;

    /**
     * On one time (Diff\Times) seconds.
     *
     * @var float
     */
    public float $on_one_time = 0;

    /**
     * Switcher for cpu calculate.
     *
     * @var bool
     */
    public bool $cpu_test = false;

    /**
     * Cpu percent.
     *
     * @var float
     */
    public float $cpu = 0;

    /**
     * Data for call tik test.
     *
     * @var \Closure|null
     */
    public \Closure|null $call_tik = null;

    /**
     * Throw event.
     *
     * @var \Closure|null
     */
    public \Closure|null $throw = null;

    /**
     * Meter constructor.
     * @param  array  $props
     */
    public function __construct(array $props)
    {
        $this->set($props);
    }

    /**
     * Set props to class.
     *
     * @param  array  $props
     * @return $this
     */
    public function set(array $props): static
    {
        foreach ($props as $key => $prop) {
            $this->{$key} = $prop;
        }

        return $this;
    }

    /**
     * Call data for meter.
     * @param  callable  $call
     * @return $this
     */
    public function start(callable $call): static
    {
        $this->mem_start = memory_get_usage();

        $this->start = hrtime(1);

        if ($this->cpu) {
            $cpu = sys_getloadavg();
            if (isset($cpu[0])) {
                $cpu = $cpu[0];
            } else {
                $cpu = null;
            }
        }

        for ($i = 1; $i <= $this->times; $i++) {
            $result = null;

            try {
                $result = call_user_func($call, $i);
            } catch (\Throwable $throwable) {
                if ($this->throw) {
                    call_user_func($this->throw, $throwable);
                }
            }

            if ($this->call_tik) {
                call_user_func($this->call_tik, $result);
            }
        }

        if ($this->cpu) {
            $cpu_end = sys_getloadavg();
            if (isset($cpu_end[0]) && isset($cpu) && $cpu) {
                $this->cpu = $cpu_end[0] - $cpu;
            }
        }

        $this->end = hrtime(1);

        $this->mem_end = memory_get_usage();

        $this->mem_diff = $this->mem_end - $this->mem_start;

        $this->diff = (($this->end - $this->start) / 1e+6) / 1000;

        $this->on_one_time = $this->times > 0 ? ($this->diff / $this->times) : 0;

        return $this;
    }

    /**
     * @param  array  $props
     * @return static
     */
    public static function create(array $props): static
    {
        return new static($props);
    }
}
