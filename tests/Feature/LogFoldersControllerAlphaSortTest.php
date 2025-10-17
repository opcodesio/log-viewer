<?php

use Opcodes\LogViewer\Enums\FolderSortingMethod;

use function Pest\Laravel\getJson;

beforeEach(function () {
    clearGeneratedLogFiles();
    config(['log-viewer.include_files' => ['*/**.log']]);
});

it('you can get alphabetically sorted default desc logs folders controller 1', function () {
    config(['log-viewer.defaults.folder_sorting_method' => FolderSortingMethod::Alphabetical]);
    config(['log-viewer.defaults.file_sorting_method' => FolderSortingMethod::Alphabetical]);
    $names = [
        'sub/one.log',
        'sub/two.log',
        'sub/three.log',
        'sub/four.log',
    ];
    generateLogFiles($names, randomContent: true);
    array_map(function (string $name) {
        $this->travelTo(now()->addSecond());
        touch(storage_path('logs/'.$name), now()->timestamp);
    }, $names);

    $response = getJson(route('log-viewer.folders'));

    expect(array_column($response->json()[0]['files'], 'name'))->toBe([
        'two.log',
        'three.log',
        'one.log',
        'four.log',
    ]);
});

it('you can get alphabetically sorted asc logs folders controller 1', function () {
    config(['log-viewer.defaults.folder_sorting_method' => FolderSortingMethod::Alphabetical]);
    config(['log-viewer.defaults.file_sorting_method' => FolderSortingMethod::Alphabetical]);
    $names = [
        'sub/one.log',
        'sub/two.log',
        'sub/three.log',
        'sub/four.log',
    ];
    generateLogFiles($names, randomContent: true);
    array_map(function (string $name) {
        $this->travelTo(now()->addSecond());
        touch(storage_path('logs/'.$name), now()->timestamp);
    }, $names);

    $response = getJson(route('log-viewer.folders', ['direction' => 'asc']));

    expect(array_column($response->json()[0]['files'], 'name'))->toBe([
        'four.log',
        'one.log',
        'three.log',
        'two.log',
    ]);
});

it('you can get alphabetically sorted desc logs folders controller 1', function () {
    config(['log-viewer.defaults.folder_sorting_method' => FolderSortingMethod::Alphabetical]);
    config(['log-viewer.defaults.file_sorting_method' => FolderSortingMethod::Alphabetical]);
    $names = [
        'sub/one.log',
        'sub/two.log',
        'sub/three.log',
        'sub/four.log',
    ];
    generateLogFiles($names, randomContent: true);
    array_map(function (string $name) {
        $this->travelTo(now()->addSecond());
        touch(storage_path('logs/'.$name), now()->timestamp);
    }, $names);

    $response = getJson(route('log-viewer.folders', ['direction' => 'desc']));

    expect(array_column($response->json()[0]['files'], 'name'))->toBe([
        'two.log',
        'three.log',
        'one.log',
        'four.log',
    ]);
});

// не работает сортировка файлов при config(['log-viewer.defaults.folder_sorting_method' => FolderSortingMethod::ModifiedTime])
it('you can get alphabetically sorted default desc logs folders controller 2', function () {
    config(['log-viewer.defaults.folder_sorting_method' => FolderSortingMethod::ModifiedTime]);
    config(['log-viewer.defaults.file_sorting_method' => FolderSortingMethod::Alphabetical]);
    $names = [
        'sub/one.log',
        'sub/two.log',
        'sub/three.log',
        'sub/four.log',
    ];
    generateLogFiles($names, randomContent: true);
    array_map(function (string $name) {
        $this->travelTo(now()->addSecond());
        touch(storage_path('logs/'.$name), now()->timestamp);
    }, $names);

    $response = getJson(route('log-viewer.folders'));

    expect(array_column($response->json()[0]['files'], 'name'))->toBe([
        'two.log',
        'three.log',
        'one.log',
        'four.log',
    ]);
});

it('you can get alphabetically sorted asc logs folders controller 2', function () {
    config(['log-viewer.defaults.folder_sorting_method' => FolderSortingMethod::ModifiedTime]);
    config(['log-viewer.defaults.file_sorting_method' => FolderSortingMethod::Alphabetical]);
    $names = [
        'sub/one.log',
        'sub/two.log',
        'sub/three.log',
        'sub/four.log',
    ];
    generateLogFiles($names, randomContent: true);
    array_map(function (string $name) {
        $this->travelTo(now()->addSecond());
        touch(storage_path('logs/'.$name), now()->timestamp);
    }, $names);

    $response = getJson(route('log-viewer.folders', ['direction' => 'asc']));

    expect(array_column($response->json()[0]['files'], 'name'))->toBe([
        'four.log',
        'one.log',
        'three.log',
        'two.log',
    ]);
});

it('you can get alphabetically sorted desc logs folders controller 2', function () {
    config(['log-viewer.defaults.folder_sorting_method' => FolderSortingMethod::ModifiedTime]);
    config(['log-viewer.defaults.file_sorting_method' => FolderSortingMethod::Alphabetical]);
    $names = [
        'sub/one.log',
        'sub/two.log',
        'sub/three.log',
        'sub/four.log',
    ];
    generateLogFiles($names, randomContent: true);
    array_map(function (string $name) {
        $this->travelTo(now()->addSecond());
        touch(storage_path('logs/'.$name), now()->timestamp);
    }, $names);

    $response = getJson(route('log-viewer.folders', ['direction' => 'desc']));

    expect(array_column($response->json()[0]['files'], 'name'))->toBe([
        'two.log',
        'three.log',
        'one.log',
        'four.log',
    ]);
});
