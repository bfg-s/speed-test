<?php

namespace Bfg\SpeedTest\Server;

use Bfg\SpeedTest\Point;

class Connection
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
    private mixed $socket = null;

    /**
     * @param  string  $host  The server host
     */
    public function __construct(string $host)
    {
        if (!str_contains($host, '://')) {
            $host = 'tcp://'.$host;
        }
        $this->host = $host;
    }

    /**
     * Write data to socket
     *
     * @param  Point  $point
     * @return bool
     */
    public function write(Point $point): bool
    {
        $this->socket = $this->createSocket();

        $encodedPayload = base64_encode(serialize($point))."\n";

        if (-1 !== stream_socket_sendto($this->socket, $encodedPayload)) {
            return true;
        }
        return false;
    }

    /**
     * Socket creator
     *
     * @return false|resource
     */
    private function createSocket()
    {
        return stream_socket_client($this->host);
    }
}
