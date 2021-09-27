<?php

namespace Bfg\SpeedTest;

use Bfg\Installer\Providers\InstalledProvider;
use Bfg\SpeedTest\Commands\MakeBenchmarkCommand;
use Bfg\SpeedTest\Commands\BenchmarkCommand;
use Bfg\SpeedTest\Commands\SpeedWatcherCommand;
use Bfg\SpeedTest\Server\Connection;
use Bfg\SpeedTest\Server\WatcherServer;

/**
 * Class ServiceProvider.
 * @package Bfg\SpeedTest
 */
class ServiceProvider extends InstalledProvider
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
     * Set as installed by default.
     * @var bool
     */
    public bool $installed = true;

    /**
     * Executed when the provider is registered
     * and the extension is installed.
     * @return void
     */
    public function installed(): void
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
     * Executed when the provider run method
     * "boot" and the extension is installed.
     * @return void
     */
    public function run(): void
    {
        $this->publishes([
            __DIR__.'/../config/speed-test.php' => config_path('speed-test.php'),
        ], 'speed-test');
    }
}
