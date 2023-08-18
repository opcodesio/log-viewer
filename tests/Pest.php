<?php

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Opcodes\LogViewer\LogFile;
use Opcodes\LogViewer\LogIndex;
use Opcodes\LogViewer\Logs\LogType;
use Opcodes\LogViewer\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);
uses()->afterEach(fn () => clearGeneratedLogFiles())->in('Feature', 'Unit');
uses()->beforeEach(fn () => Artisan::call('log-viewer:publish'))->in('Feature');
uses()->beforeEach(function () {
    // let's not include any of the default mac logs or similar
    config(['log-viewer.include_files' => ['*.log', '**/*.log']]);
})->in('Unit', 'Feature');

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

/**
 * Generate log files with random data
 */
function generateLogFiles(array $files, string $content = null, bool $randomContent = false, $type = LogType::LARAVEL): array
{
    return array_map(
        fn ($file) => generateLogFile($file, $content, $randomContent, $type),
        $files
    );
}

function generateLogFile(string $fileName = null, string $content = null, bool $randomContent = false, $type = LogType::LARAVEL): LogFile
{
    if (is_null($fileName)) {
        $fileName = \Illuminate\Support\Str::random().'.log';
    }

    $path = storage_path('logs'.DIRECTORY_SEPARATOR.$fileName);
    $folder = dirname($path);

    if (! File::isDirectory($folder)) {
        File::makeDirectory($folder, 0755, true);
    }

    if (File::exists($path)) {
        File::delete($path);
    }

    File::put($path, $content ?? ($randomContent ? dummyLogData(type: $type) : ''));

    // we perform a regular PHP assertion, so it doesn't count towards the unit test assertion count.
    assert(file_exists($path));

    return new LogFile($path);
}

function dummyLogData(int $lines = null, string $type = LogType::LARAVEL): string
{
    if (is_null($lines)) {
        $lines = rand(1, 10);
    }

    return implode(PHP_EOL, array_map(
        fn ($_) => match ($type) {
            LogType::LARAVEL => makeLaravelLogEntry(),
            LogType::HTTP_ACCESS => makeHttpAccessLogEntry(),
            LogType::HTTP_ERROR_APACHE => makeHttpApacheErrorLogEntry(),
            LogType::HTTP_ERROR_NGINX => makeHttpNginxErrorLogEntry(),
        },
        range(1, $lines)
    ));
}

function clearGeneratedLogFiles(): void
{
    File::cleanDirectory(storage_path('logs'));
    clearstatcache();
}

function makeLaravelLogEntry(CarbonInterface $date = null, string $level = 'debug', string $message = 'Testing log entry'): string
{
    $dateFormatted = $date instanceof CarbonInterface ? $date->toDateTimeString() : now()->toDateTimeString();
    $level = strtoupper($level);

    return "[$dateFormatted] local.$level: $message";
}

function makeHttpAccessLogEntry(CarbonInterface $date = null, string $method = 'get', string $path = '/app', int $statusCode = 200, int $contentLength = null): string
{
    $randomIp = rand(1, 255).'.'.rand(1, 255).'.'.rand(1, 255).'.'.rand(1, 255);
    $dateFormatted = $date instanceof CarbonInterface ? $date->format('d/M/Y:H:i:s O') : now()->format('d/M/Y:H:i:s O');
    $method = strtoupper($method);
    $contentLength ??= rand(1, 9999);

    return <<<EOF
$randomIp - - [$dateFormatted] "$method $path HTTP/2.0" $statusCode $contentLength "http://www.example.com/post.php" "Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; AS; rv:11.0) like Gecko"
EOF;
}

function makeHttpApacheErrorLogEntry(CarbonInterface $date = null, string $module = null, string $level = null, int $pid = null, string $client = null, string $message = null): string
{
    $dateFormatted = $date instanceof CarbonInterface ? $date->format('D M d H:i:s.u Y') : now()->format('D M d H:i:s.u Y');
    $module ??= 'php';
    $level ??= 'error';
    $pid ??= rand(1, 9999);
    $client ??= rand(1, 255).'.'.rand(1, 255).'.'.rand(1, 255).'.'.rand(1, 255);
    $message ??= 'Testing log entry';

    return <<<EOF
[$dateFormatted] [$module:$level] [pid $pid] [client $client] $message
EOF;
}

function makeHttpNginxErrorLogEntry(CarbonInterface $date = null, string $level = null, string $message = null, string $client = null, string $server = null, string $request = null, string $host = null): string
{
    $dateFormatted = $date instanceof CarbonInterface ? $date->format('Y/m/d H:i:s') : now()->format('Y/m/d H:i:s');
    $level ??= 'error';
    $pid ??= rand(1, 9999);
    $client ??= rand(1, 255).'.'.rand(1, 255).'.'.rand(1, 255).'.'.rand(1, 255);
    $message ??= 'Testing log entry';
    $server ??= '127.0.0.1:80';
    $request ??= 'GET / HTTP/1.1';
    $host ??= 'localhost';

    return <<<EOF
$dateFormatted [$level] 23263#0: $message, client: $client, server: $server, request: "$request", host: "$host"
EOF;
}

function createLogIndex($file = null, $query = null, array $predefinedLogs = []): LogIndex
{
    if (is_null($file)) {
        $file = new LogFile('test.log');
    }

    $logIndex = new LogIndex($file, $query);

    foreach ($predefinedLogs as $predefinedLog) {
        $logIndex->addToIndex(...$predefinedLog);
    }

    $logIndex->save();

    return $logIndex;
}
