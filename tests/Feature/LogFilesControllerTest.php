<?php

use function Pest\Laravel\getJson;

beforeEach(function () {
    config(['log-viewer.include_files' => ['*.log']]);
});

it('can get the log files', function () {
    $files = generateLogFiles([
        '1.one.log',
        '2.two.log',
        '3.three.log',
    ], randomContent: true);

    $response = getJson(route('log-viewer.files'));

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'folders']);

    $response->assertJsonCount(count($files), 'data')
        ->assertJsonCount(count($files), 'folders');
});
