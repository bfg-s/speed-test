<?php

return [
    'dir' => base_path('tests/Benchmark'),
    'namespace' => 'Tests\\Benchmark',
    'watcher_host' => env('WATCHER_HOST', 'tcp://127.0.0.1:4554')
];
