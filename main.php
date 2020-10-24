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
    ob_start();
    $start = microtime(true);
    $f();
    $diff = ceil((microtime(true) - $start) * 1000);
    echo "diff {$diff}\n";
    $output = ob_get_clean();

    if ($diff > 200) {
        echo $output;
    }
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
