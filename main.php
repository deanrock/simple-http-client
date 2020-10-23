<?php
declare(strict_types=1);
declare(ticks=1);

use GuzzleHttp\Client;

require_once 'vendor/autoload.php';

function makeRequest()
{
    $client = new Client();
    $timeout = 5;

    $res = $client->request('GET', getenv('URL'), [
        'headers' => [],
        'connect_timeout' => $timeout,
        'read_timeout' => $timeout,
        'timeout' => $timeout,
    ]);

    if ($res->getStatusCode() == 200) {
        json_decode((string)$res->getBody());
    }
}

function timeExecution(Closure $f)
{
    $start = microtime(true);
    $f();
    $diff = ceil((microtime(true) - $start) * 1000);
    echo "diff {$diff}\n";
}

function handleException(Closure $f)
{
    try {
        $f();
    }catch (Exception $e) {
        echo "exception {$e->getMessage()}";
    }
}

function shutdown()
{
    exit;
}

pcntl_signal(SIGINT,"shutdown");

while (true) {
    timeExecution(function() {
        handleException(function() {
            makeRequest();
        });
    });
}
