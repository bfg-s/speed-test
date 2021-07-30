<?php

namespace Bfg\SpeedTest\Commands;

use Bfg\Dev\Interfaces\SpeedTestInterface;
use Bfg\Entity\Core\Entities\DocumentorEntity;
use Bfg\SpeedTest\Meter;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\SplFileInfo;

class SpeedTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'speed:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Speed test runner';

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
        $dir = config('speed-test.dir');

        $namespace = config('speed-test.namespace');

        $files = \File::allFiles($dir);

        $classes = collect($files)->map(function (SplFileInfo $file) {
            return class_in_file($file->getRealPath());
        });

        $test = $this->argument('test');

        $test_method = null;

        if ($test) {

            [$test, $test_method] = \Str::parseCallback($test);

            $test = ucfirst(\Str::camel($test));

            $classes = $classes->filter(fn (string $class) => $class == "{$namespace}\\{$test}");
        }

        $classes = $classes->map(function (string $class) use ($test_method) {
            $ref = new \ReflectionClass($class);
            $methods = collect($ref->getMethods(\ReflectionMethod::IS_PUBLIC));
            if ($test_method) {
                $methods = $methods->filter(fn (\ReflectionMethod $method) => $method->name == $test_method);
            }
            $class_instance = new $class;
            $methods = $methods->map(function (\ReflectionMethod $method) use ($class_instance) {
                return [
                    'name' => $method->name,
                    'class' => $method->class,
                    'class_instance' => $class_instance,
                    'props' => DocumentorEntity::get_variables($method->getDocComment()),
                    'description' => pars_description_from_doc($method->getDocComment())
                ];
            });
            return $methods;
        })->collapse();

        $classes = $classes->map(function (array $item, $key) {

            $this->info(($key ? "\n\n":"") . $item['description']);

            $meter = Meter::create($item['props']);

            $bar = $this->output->createProgressBar($meter->times);

            $bar->start();

            $meter->set(['call_tik' => fn () => $bar->advance()]);

            $meter->set([
                'throw' => function (\Throwable $throwable) {

                    if ($this->option('verbose')) {
                        throw $throwable;
                    } else {
                        $this->error($throwable->getMessage());
                    }
                }
            ]);

            $meter->start([$item['class_instance'], $item['name']]);

            $bar->finish();

            $item['result'] = $meter;

            return $item;
        });

        $this->line("\n");
        $this->line("Result table:");

        $headers = ["Test", "Description", "Times", "Seconds in work", "Seconds per iteration"];

        $classes = $classes->map(function (array $item) {
            return [
                $item['class'] . "@" . $item['name'],
                $item['description'],
                "<comment>".$item['result']->times."</comment>",
                "<info>".$item['result']->diff."</info>",
                "<info>".$item['result']->on_one_time."</info>",
            ];
        });

        $this->table($headers, $classes->toArray());

        return 0;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['test', InputArgument::OPTIONAL, 'The name of the test case.'],
        ];
    }

    /**
     * @return array
     */
    protected function getOptions()
    {
        return [
            //['tries', 't', InputOption::VALUE_OPTIONAL, 'Count of tries [default=1000]'],
        ];
    }
}
