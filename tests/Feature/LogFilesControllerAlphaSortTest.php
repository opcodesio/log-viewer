<?php

use Opcodes\LogViewer\Enums\SortingMethod;

use function Pest\Laravel\getJson;

beforeEach(function () {
    config(['log-viewer.include_files' => ['*.log']]);
});

it('you can get alphabetically sorted default desc logs files controller 1', function () {
    config(['log-viewer.defaults.folder_sorting_method' => SortingMethod::Alphabetical]);
    config(['log-viewer.defaults.file_sorting_method' => SortingMethod::Alphabetical]);

    generateLogFiles([
        'one.log',
        'two.log',
        'three.log',
        'four.log',
    ], randomContent: true);

    $response = getJson(route('log-viewer.files'));

    expect(array_column($response->json(), 'name'))->toBe([
        'two.log',
        'three.log',
        'one.log',
        'four.log',
    ]);
});

it('you can get alphabetically sorted asc logs files controller 1', function () {
    config(['log-viewer.defaults.folder_sorting_method' => SortingMethod::Alphabetical]);
    config(['log-viewer.defaults.file_sorting_method' => SortingMethod::Alphabetical]);

    generateLogFiles([
        'one.log',
        'two.log',
        'three.log',
        'four.log',
    ], randomContent: true);

    $response = getJson(route('log-viewer.files', ['direction' => 'asc']));

    expect(array_column($response->json(), 'name'))->toBe([
        'four.log',
        'one.log',
        'three.log',
        'two.log',
    ]);
});

it('you can get alphabetically sorted desc logs files controller 1', function () {
    config(['log-viewer.defaults.folder_sorting_method' => SortingMethod::Alphabetical]);
    config(['log-viewer.defaults.file_sorting_method' => SortingMethod::Alphabetical]);

    generateLogFiles([
        'one.log',
        'two.log',
        'three.log',
        'four.log',
    ], randomContent: true);

    $response = getJson(route('log-viewer.files', ['direction' => 'desc']));

    expect(array_column($response->json(), 'name'))->toBe([
        'two.log',
        'three.log',
        'one.log',
        'four.log',
    ]);
});

it('you can get alphabetically sorted default desc logs files controller 2', function () {
    config(['log-viewer.defaults.folder_sorting_method' => SortingMethod::ModifiedTime]);
    config(['log-viewer.defaults.file_sorting_method' => SortingMethod::Alphabetical]);

    generateLogFiles([
        'one.log',
        'two.log',
        'three.log',
        'four.log',
    ], randomContent: true);

    $response = getJson(route('log-viewer.files'));

    expect(array_column($response->json(), 'name'))->toBe([
        'two.log',
        'three.log',
        'one.log',
        'four.log',
    ]);
});

it('you can get alphabetically sorted asc logs files controller 2', function () {
    config(['log-viewer.defaults.folder_sorting_method' => SortingMethod::ModifiedTime]);
    config(['log-viewer.defaults.file_sorting_method' => SortingMethod::Alphabetical]);

    generateLogFiles([
        'one.log',
        'two.log',
        'three.log',
        'four.log',
    ], randomContent: true);

    $response = getJson(route('log-viewer.files', ['direction' => 'asc']));

    expect(array_column($response->json(), 'name'))->toBe([
        'four.log',
        'one.log',
        'three.log',
        'two.log',
    ]);
});

it('you can get alphabetically sorted desc logs files controller 2', function () {
    config(['log-viewer.defaults.folder_sorting_method' => SortingMethod::ModifiedTime]);
    config(['log-viewer.defaults.file_sorting_method' => SortingMethod::Alphabetical]);

    generateLogFiles([
        'one.log',
        'two.log',
        'three.log',
        'four.log',
    ], randomContent: true);

    $response = getJson(route('log-viewer.files', ['direction' => 'desc']));

    expect(array_column($response->json(), 'name'))->toBe([
        'two.log',
        'three.log',
        'one.log',
        'four.log',
    ]);
});
