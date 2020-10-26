<?php
declare(strict_types=1);
declare(ticks=1);

use GuzzleHttp\Client;

require_once 'vendor/autoload.php';

function unparse_url(array $parsed): string {
    $pass      = $parsed['pass'] ?? null;
    $user      = $parsed['user'] ?? null;
    $userinfo  = $pass !== null ? "$user:$pass" : $user;
    $port      = $parsed['port'] ?? 0;
    $scheme    = $parsed['scheme'] ?? "";
    $query     = $parsed['query'] ?? "";
    $fragment  = $parsed['fragment'] ?? "";
    $authority = (
        ($userinfo !== null ? "$userinfo@" : "") .
        ($parsed['host'] ?? "") .
        ($port ? ":$port" : "")
    );
    return (
        (\strlen($scheme) > 0 ? "$scheme:" : "") .
        (\strlen($authority) > 0 ? "//$authority" : "") .
        ($parsed['path'] ?? "") .
        (\strlen($query) > 0 ? "?$query" : "") .
        (\strlen($fragment) > 0 ? "#$fragment" : "")
    );
}

function makeRequest(int $iteration, $url)
{
    $client = new Client();
    $timeout = 5;

    $e = explode("-", getenv('HOSTNAME'));
    $hostname = end($e);

    $url = $url . "?iter={$iteration}-{$hostname}";

    $res = $client->request('GET', $url, [
        'headers' => [],
        'connect_timeout' => $timeout,
        'read_timeout' => $timeout,
        'timeout' => $timeout,
    ]);

    echo $url . "\n";

    if ($res->getStatusCode() == 200) {
        json_decode((string)$res->getBody());
    }
}

function timeExecution(int $iteration, Closure $f)
{
    ob_start();
    $start = microtime(true);
    $f();
    $diff = ceil((microtime(true) - $start) * 1000);
    echo "\n[iter {$iteration}] diff {$diff}\n";
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

$parsed_url = parse_url(getenv('URL'));
$endpoints = gethostbynamel($parsed_url['host']);

$urlWithRandomEndpoint = function() use ($parsed_url, $endpoints): string {
    $parsed_url['host'] = $endpoints[array_rand($endpoints)];

    return unparse_url($parsed_url);
};

$iteration = 0;

while (true) {
    $iteration++;
    
    timeExecution($iteration, function() use ($iteration, $urlWithRandomEndpoint) {
        handleException(function() use ($iteration, $urlWithRandomEndpoint) {
            makeRequest($iteration, $urlWithRandomEndpoint());
        });
    });
}
