<?php

namespace Bfg\SpeedTest\Server;

use Bfg\SpeedTest\Point;
use Bfg\SpeedTest\PointSeparator;
use Illuminate\Console\Command;

class WatcherServer
{
    /**
     * The host of server
     *
     * @var string
     */
    private string $host;

    /**
     * Socket resource
     *
     * @var resource
     */
    private mixed $socket;

    /**
     * Console for log
     *
     * @var Command|null
     */
    private ?Command $logger;

    /**
     * @param  string  $host
     * @param  Command|null  $logger
     */
    public function __construct(string $host, Command $logger = null)
    {
        if (!str_contains($host, '://')) {
            $host = 'tcp://'.$host;
        }
        $this->host = $host;
        $this->logger = $logger;
    }

    /**
     * Start server
     */
    public function start(): void
    {
        if (!$this->socket = stream_socket_server($this->host, $err_no, $err_str)) {
            throw new \RuntimeException(
                sprintf('Server start failed on "%s": ', $this->host).$err_str.' '.$err_no
            );
        }
    }

    /**
     * Listen messages
     * @param  callable  $callback
     */
    public function listen(callable $callback): void
    {
        if (null === $this->socket) {
            $this->start();
        }

        foreach ($this->getMessages() as $clientId => $message) {
            $payload = @unserialize(base64_decode($message), ['allowed_classes' => [Point::class, PointSeparator::class]]);
            if (false === $payload) {
                $this->logger?->error("Unable to decode a message from {$clientId} client.");
                continue;
            }
            if (!$payload instanceof Point) {
                $this->logger?->error("Invalid payload from {$clientId} client. Expected element (Point)");
                continue;
            }
            $callback($payload, $clientId);
        }
    }

    public function getHost(): string
    {
        return $this->host;
    }

    private function getMessages(): iterable
    {
        $sockets = [(int) $this->socket => $this->socket];
        $write = [];

        while (true) {
            $read = $sockets;
            stream_select($read, $write, $write, null);

            foreach ($read as $stream) {
                if ($this->socket === $stream) {
                    $stream = stream_socket_accept($this->socket);
                    $sockets[(int) $stream] = $stream;
                } elseif (feof($stream)) {
                    unset($sockets[(int) $stream]);
                    fclose($stream);
                } else {
                    yield (int) $stream => fgets($stream);
                }
            }
        }
    }
}
