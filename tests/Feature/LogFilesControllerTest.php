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

    expect($response->json())->not->toHaveKey('data');
    $response->assertJsonCount(count($files))
        ->assertJsonFragment([
            'name' => $files[0]->name,
            'size' => $files[0]->size(),
        ])
        ->assertJsonFragment([
            'name' => $files[1]->name,
            'size' => $files[1]->size(),
        ])
        ->assertJsonFragment([
            'name' => $files[2]->name,
            'size' => $files[2]->size(),
        ]);
});
