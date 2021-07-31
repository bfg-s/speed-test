<?php

namespace Bfg\SpeedTest\Commands;

use Bfg\Dev\Interfaces\SpeedTestInterface;
use Bfg\Entity\Core\Entities\DocumentorEntity;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakeTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:bench {test? : The test name}
        {--d|description= : The description of test}
        {--t|times=10 : Number of iterations}
        {--l|line=// : Code line in the icted function}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Speed test maker';

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
        $test = $this->argument('test');

        $dir = config('speed-test.dir');

        $className = ucfirst(\Str::camel($test));

        $namespace = config('speed-test.namespace');

        $class = class_entity($className)
            ->namespace($namespace);

        $class->method('speed1')
            ->line($this->option('line'))->doc(function ($doc) use ($className) {
                /** @var DocumentorEntity $doc */
                $doc->tagCustom('times', $this->option('times'));
                $doc->description($this->option("description") ?: "{$className} Speed 1");
            });

        if (!is_dir($dir)) {

            mkdir($dir, 0777, 1);
        }

        file_put_contents(
            $dir . '/' . $className . '.php',
            $class->wrap('php')
        );

        $this->info("Speed created!");

        return 0;
    }
}
