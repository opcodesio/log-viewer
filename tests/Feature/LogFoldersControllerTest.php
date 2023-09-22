<?php

use function Pest\Laravel\getJson;

beforeEach(function () {
    config(['log-viewer.include_files' => ['*/**.log']]);
});

it('can get the log files', function () {
    generateLogFiles([
        'one/1.one.log',
        'one/2.two.log',
        'two/3.three.log',
    ], randomContent: true);

    $response = getJson(route('log-viewer.folders'));

    expect($response->json())->not->toHaveKey('data');
    $response->assertJsonCount(2)
        ->assertJsonFragment(['clean_path' => 'root/one'])
        ->assertJsonFragment(['clean_path' => 'root/two']);
});
