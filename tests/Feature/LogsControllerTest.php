<?php

use function Pest\Laravel\getJson;

it('can load the logs for a specific file', function () {
    $logEntries = [
        makeLaravelLogEntry(),
        makeLaravelLogEntry(),
        makeLaravelLogEntry(),
    ];
    $file = generateLogFile('test.log', implode(PHP_EOL, $logEntries));
    dump($file);
    dump(\Opcodes\LogViewer\Facades\LogViewer::getFiles());

    $response = getJson(route('log-viewer.logs', ['file' => $file->identifier]));

    dump($file->contents());
    dump($response->json());

    expect($response->json('logs'))->toHaveCount(count($logEntries));
});
