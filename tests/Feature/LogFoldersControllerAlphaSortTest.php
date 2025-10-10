<?php

use Opcodes\LogViewer\Enums\FolderSortingMethod;

use function Pest\Laravel\getJson;

beforeEach(function () {
    clearGeneratedLogFiles();
    config(['log-viewer.include_files' => ['*/**.log']]);
});

it('you can get alphabetically sorted default desc logs folders controller', function () {
    config(['log-viewer.defaults.file_sorting_method' => FolderSortingMethod::Alphabetical]);

    generateLogFiles([
        'sub/one.log',
        'sub/two.log',
        'sub/three.log',
        'sub/four.log',
    ], randomContent: true);

    $response = getJson(route('log-viewer.folders'));

    expect(array_column($response->json()[0]['files'], 'name'))->toBe([
        'two.log',
        'three.log',
        'one.log',
        'four.log',
    ]);
});

it('you can get alphabetically sorted asc logs folders controller', function () {
    config(['log-viewer.defaults.file_sorting_method' => FolderSortingMethod::Alphabetical]);

    generateLogFiles([
        'sub/one.log',
        'sub/two.log',
        'sub/three.log',
        'sub/four.log',
    ], randomContent: true);

    $response = getJson(route('log-viewer.folders', ['direction' => 'asc']));

    expect(array_column($response->json()[0]['files'], 'name'))->toBe([
        'four.log',
        'one.log',
        'three.log',
        'two.log',
    ]);
});

it('you can get alphabetically sorted desc logs folders controller', function () {
    config(['log-viewer.defaults.file_sorting_method' => FolderSortingMethod::Alphabetical]);

    generateLogFiles([
        'sub/one.log',
        'sub/two.log',
        'sub/three.log',
        'sub/four.log',
    ], randomContent: true);

    $response = getJson(route('log-viewer.folders', ['direction' => 'desc']));

    expect(array_column($response->json()[0]['files'], 'name'))->toBe([
        'two.log',
        'three.log',
        'one.log',
        'four.log',
    ]);
});
