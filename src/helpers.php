<?php

if (!function_exists('bpoint')) {
    /**
     * Make Bench point
     * @param  mixed|null  $message
     * @return \Bfg\SpeedTest\Point
     */
    function bpoint (mixed $message = null): \Bfg\SpeedTest\Point
    {
        $trace = debug_backtrace()[0];
        return \Bfg\SpeedTest\Point::make($message, $trace);
    }
}
