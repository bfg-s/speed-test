<?php

namespace Bfg\SpeedTest;

use Bfg\Installer\Providers\InstalledProvider;
use Bfg\SpeedTest\Commands\MakeTestCommand;
use Bfg\SpeedTest\Commands\SpeedTestCommand;

/**
 * Class ServiceProvider
 * @package Bfg\SpeedTest
 */
class ServiceProvider extends InstalledProvider
{
    /**
     * The description of extension.
     * @var string|null
     */
    public ?string $description = "Speed check tool";

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
            MakeTestCommand::class,
            SpeedTestCommand::class
        ]);
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

