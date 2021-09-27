<?php

namespace Bfg\SpeedTest\Commands;

use Bfg\SpeedTest\Point;
use Bfg\SpeedTest\PointSeparator;
use Bfg\SpeedTest\Server\WatcherServer;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\VarDumper\Cloner\Data;

class SpeedWatcherCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'speed:watcher';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start speed watcher';

    /**
     * @var WatcherServer|null
     */
    protected WatcherServer|null $server = null;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->server = new WatcherServer(
            config('speed-test.watcher_host'),
            $this
        );

        $io = new SymfonyStyle($this->input, $this->output);

        $errorIo = $io->getErrorStyle();
        $errorIo->title('Laravel Speed Watcher Server');

        $this->server->start();

        $errorIo->success(sprintf('Server listening on %s', $this->server->getHost()));
        $errorIo->comment('Quit the server with CONTROL-C.');

        $prev = 0;

        $this->server->listen(function (Point $point) use ($io, &$prev) {
            if ($point instanceof PointSeparator) {
                $io->title($point->message);
            } else {

                $r = round($point->diff, 4);

//                if ($r == 0) {
//
//                }
//
//                $header = [];
//
//                $header[] = 'File';
//                $header[] = 'Time';
//                $header[] = 'Message';

                $io->horizontalTable([
                    'File',
                    'Time',
                    'Message',
                    'Diff sec',
                ], [[
                    str_replace(base_path().'/', '', $point->trace['file']),
                    Carbon::parse($point->time)->format('H:i:s.u'),
                    is_array($point->message) ? json_encode($point->message) : $point->message,
                    $r,
                ]]);
            }
        });
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
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            //['test', InputArgument::OPTIONAL, 'The name of the test case [Optional].'],
        ];
    }

    /**
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            //['times', 't', InputOption::VALUE_OPTIONAL, 'Number of iterations for all'],
            //['ls', 'l', InputOption::VALUE_NONE, 'Show list of tests'],
        ];
    }
}
