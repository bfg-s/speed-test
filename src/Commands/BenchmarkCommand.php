<?php

namespace Bfg\SpeedTest\Commands;

use Bfg\Dev\Interfaces\SpeedTestInterface;
use Bfg\Entity\Core\Entities\DocumentorEntity;
use Bfg\SpeedTest\Meter;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\SplFileInfo;

class BenchmarkCommand extends Command
{
    /**
     * @var int
     */
    protected $total_bytes = 0;

    /**
     * @var int
     */
    protected $total_seconds = 0;

    /**
     * @var string|null
     */
    protected $dir = null;

    /**
     * @var string|null
     */
    protected $namespace = null;

    /**
     * @var bool
     */
    protected $linux = false;

    /**
     * Cpu percent.
     *
     * @var float
     */
    protected float $cpu = 0;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'benchmark';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start benchmark';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->dir = config('speed-test.dir');

        $this->namespace = config('speed-test.namespace');

        $files = \File::allFiles($this->dir);

        $classes = collect($files)->map(function (SplFileInfo $file) {
            return class_in_file($file->getRealPath());
        });

        $this->linux = ! (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

        if ($this->linux) {
            $cpu = sys_getloadavg();
            if (isset($cpu[0])) {
                $this->cpu = $cpu[0];
            }
        }

        ProgressBar::setFormatDefinition(
            'debug',
            ' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% %message%'
        );

        $test = $this->argument('test');

        $test_method = null;

        if ($test) {
            [$test, $test_method] = \Str::parseCallback($test);

            $test = ucfirst(\Str::camel($test));

            $classes = $classes->filter(fn (string $class) => $class == "{$this->namespace}\\{$test}");
        }

        $classes = $this->preparationClasses($classes, $test_method);

        $this->makeList($classes);

        $classes = $this->runTestClasses($classes);

        $this->makeResultTables($classes);

        return 0;
    }

    /**
     * Make list of tests.
     * @param  Collection  $classes
     */
    protected function makeList(Collection $classes)
    {
        if ($this->option('ls')) {
            $classes = $classes->map(function (array $item) {
                $meter = Meter::create($item['props']);

                $times = $this->option('times') ?: $meter->times;

                if ($times) {
                    $meter->set(['times' => $times]);
                }

                return [
                    $item['class'],
                    \Str::snake(str_replace($this->namespace.'\\', '', $item['class'])).'@'.$item['name'],
                    $item['description'],
                    '<comment>'.$times.'</comment>',
                ];
            });
            $headers = ['Class', 'Test', 'Description', 'Times'];
            $this->table($headers, $classes->toArray());
            exit(0);
        }
    }

    /**
     * Preparation of process classes.
     * @param  Collection  $classes
     * @param $test_method
     * @return Collection
     */
    protected function preparationClasses(Collection $classes, $test_method): Collection
    {
        return $classes->map(function (string $class) use ($test_method) {
            $ref = new \ReflectionClass($class);
            $methods = collect($ref->getMethods(\ReflectionMethod::IS_PUBLIC));
            if ($test_method) {
                $methods = $methods->filter(fn (\ReflectionMethod $method) => $method->name == $test_method);
            }
            $class_instance = new $class;

            return $methods->map(function (\ReflectionMethod $method) use ($class_instance, $ref) {
                return [
                    'file' => $ref->getFileName(),
                    'name' => $method->name,
                    'class' => $method->class,
                    'class_instance' => $class_instance,
                    'props' => DocumentorEntity::get_variables($method->getDocComment()),
                    'description' => pars_description_from_doc($method->getDocComment()),
                ];
            });
        })->collapse();
    }

    /**
     * Run all selected tests.
     * @param  Collection  $classes
     * @return Collection
     */
    protected function runTestClasses(Collection $classes): Collection
    {
        return $classes->map(function (array $item, $key) {
            $this->info(($key ? "\n\n" : '').$item['description']);

            $meter = Meter::create($item['props']);

            $times = $this->option('times') ?: $meter->times;

            $meter->times = $times;

            if ($times) {
                $meter->set(['times' => $times]);
            }

            $bar = $this->output->createProgressBar($times);

            $bar->setMessage('');

            $bar->start();

            $bar->setFormat('debug');

            $meter->set(['call_tik' => function ($result) use ($bar) {
                $bar->advance();
                $bar->setMessage($result ? "/ {$result}" : '');
                gc_collect_cycles();
            }]);

            $meter->set([
                'cpu_test' => $this->linux,
                'throw' => function (\Throwable $throwable) {
                    if ($this->option('verbose')) {
                        throw $throwable;
                    } else {
                        $this->error("\n".$throwable->getMessage());
                    }
                },
            ]);

            $meter->start([$item['class_instance'], $item['name']]);

            $bar->finish();

            $item['result'] = $meter;

            return $item;
        });
    }

    /**
     * Make result stat table.
     * @param  Collection  $classes
     * @return Collection
     */
    protected function makeResultTables(Collection $classes): Collection
    {
        $this->line("\n\n");

        $diff_len = 0;
        $on_one_time_len = 0;
        $mem_diff_len = 0;
        $mem_diff_times_len = 0;

        $classes = $classes->map(function (array $item) use (&$diff_len, &$on_one_time_len, &$mem_diff_len, &$mem_diff_times_len) {
            $this->total_bytes += $item['result']->mem_diff;
            $this->total_seconds += $item['result']->diff;

            $diff = round($item['result']->diff, 4);
            $on_one_time = round($item['result']->on_one_time, 6);
            $mem_diff = $this->humanSize($item['result']->mem_diff);
            $mem_diff_times = $this->humanSize($item['result']->mem_diff / $item['result']->times);

            $diff_len = strlen($diff) > $diff_len ? strlen($diff) : $diff_len;
            $on_one_time_len = strlen($on_one_time) > $on_one_time_len ? strlen($on_one_time) : $on_one_time_len;
            $mem_diff_len = strlen($mem_diff) > $mem_diff_len ? strlen($mem_diff) : $mem_diff_len;
            $mem_diff_times_len = strlen($mem_diff_times) > $mem_diff_times_len ? strlen($mem_diff_times) : $mem_diff_times_len;

            $item['calc'] = [
                $diff,
                $on_one_time,
                $mem_diff,
                $mem_diff_times,
            ];

            return $item;
        })->map(function (array $item) use ($diff_len, $on_one_time_len, $mem_diff_len, $mem_diff_times_len) {
            $add = [];

            if ($this->linux) {
                $add[] = "<info>{$item['result']->cpu}</info>";
            }

            return array_merge([
                \Str::snake(str_replace($this->namespace.'\\', '', $item['class'])).'@'.$item['name'],
                $item['description'],
                '<comment>'.$item['result']->times.'</comment>',

                '<info>'.$item['calc'][0].($diff_len > strlen($item['calc'][0]) ? str_repeat(' ', $diff_len - strlen($item['calc'][0])) : '').'</info> | '.
                '<info>'.($on_one_time_len > strlen($item['calc'][1]) ? str_repeat(' ', $on_one_time_len - strlen($item['calc'][1])) : '').$item['calc'][1].' sec</info>',

                '<comment>'.$item['calc'][2].($mem_diff_len > strlen($item['calc'][2]) ? str_repeat(' ', $mem_diff_len - strlen($item['calc'][2])) : '').'</comment> | '.
                '<comment>'.($mem_diff_times_len > strlen($item['calc'][3]) ? str_repeat(' ', $mem_diff_times_len - strlen($item['calc'][3])) : '').$item['calc'][3].'</comment>',
            ], $add);
        });

        $mem_usage_end = memory_get_usage();

        $headers = ['Test', 'Description', 'Times', 'In work', 'Used memory'];
        $stats_header = [
            'Test count', 'Total test seconds', 'Total test memory', 'Usage memory', 'Free memory', 'Memory limit',
        ];
        $stats_add = [];

        if ($this->linux) {
            $headers[] = 'Used % CPU';

            $cpu = sys_getloadavg();

            if (isset($cpu[0])) {
                $cpu = $cpu[0];
                $stats_header[] = 'Current \\ Total used % CPU';
                $stats_add[] = $cpu.' \\ '.$cpu - $this->cpu;
            }
        }

        $this->table(
            $stats_header,
            [array_merge([
                $classes->count(),
                '<comment>'.round($this->total_seconds, 4).' sec</comment>',
                '<comment>'.$this->humanSize($this->total_bytes).'</comment>',
                '<comment>'.$this->humanSize($mem_usage_end).'</comment>',
                '<comment>'.$this->humanSize($this->get_memory_limit() - $mem_usage_end).'</comment>',
                '<comment>'.$this->humanSize($this->get_memory_limit()).'</comment>',
            ], $stats_add)]
        );

        $this->table($headers, $classes->toArray());

        return $classes;
    }

    /**
     * Convert seconds to time.
     * @param $seconds
     * @return string
     */
    protected function secondsToTime($seconds): string
    {
        $dtF = new \DateTime('@0');
        $dtT = new \DateTime("@$seconds");

        return $dtF->diff($dtT)->format('%a days, %h hours, %i minutes and %s.%u seconds');
    }

    /**
     * Human size converter from bytes.
     * @param $bytes
     * @param  int  $dec
     * @return string
     */
    protected function humanSize($bytes, int $dec = 8): string
    {
        $size = ['b', 'kb', 'mb', 'gb', 'tb', 'pb', 'eb', 'zb', 'yb'];
        $factor = floor((strlen($bytes) - 1) / 3);

        $val = rtrim(sprintf("%.{$dec}f", $bytes / pow(1024, $factor)), '.0');

        return  ($val ?: '0.0').(isset($size[$factor]) ? " {$size[$factor]}" : '');
    }

    /**
     * Get PHP memory limit.
     * @return int
     */
    protected function get_memory_limit(): int
    {
        $limit_string = ini_get('memory_limit');
        $unit = strtolower(mb_substr($limit_string, -1));
        $bytes = intval(mb_substr($limit_string, 0, -1), 10);

        switch ($unit) {
            case 'k':
                $bytes *= 1024;
                break;

            case 'm':
                $bytes *= 1048576;
                break;

            case 'g':
                $bytes *= 1073741824;
                break;

            default:
                break;
        }

        return $bytes;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            ['test', InputArgument::OPTIONAL, 'The name of the test case [Optional].'],
        ];
    }

    /**
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['times', 't', InputOption::VALUE_OPTIONAL, 'Number of iterations for all'],
            ['ls', 'l', InputOption::VALUE_NONE, 'Show list of tests'],
        ];
    }
}
