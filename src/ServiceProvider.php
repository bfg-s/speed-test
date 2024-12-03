<?php

namespace Bfg\SpeedTest;

use Bfg\SpeedTest\Commands\MakeBenchmarkCommand;
use Bfg\SpeedTest\Commands\BenchmarkCommand;
use Bfg\SpeedTest\Commands\SpeedWatcherCommand;
use Bfg\SpeedTest\Server\Connection;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

/**
 * Class ServiceProvider.
 * @package Bfg\SpeedTest
 */
class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * The connection to the watcher server
     * @var Connection|null
     */
    static protected ?Connection $connection = null;

    /**
     * @var array
     */
    static public array $points = [];

    /**
     * Register route settings.
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/speed-test.php', 'speed-test'
        );

        $this->commands([
            MakeBenchmarkCommand::class,
            BenchmarkCommand::class,
            SpeedWatcherCommand::class,
        ]);

        register_shutdown_function(function () {
            if (static::$points && config('speed-test.watcher_host')) {
                static::$connection = new Connection(
                    config('speed-test.watcher_host')
                );
                foreach (static::$points as $point) {
                    static::$connection->write($point);
                }
                static::$connection->write(app(PointSeparator::class));
            }
        });
    }

    /**
     * Bootstrap services.
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/speed-test.php' => config_path('speed-test.php'),
        ], 'speed-test');
    }
}
