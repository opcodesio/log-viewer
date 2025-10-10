<?php

use Opcodes\LogViewer\Enums\FolderSortingMethod;

use function Pest\Laravel\getJson;

beforeEach(function () {
    config(['log-viewer.include_files' => ['*.log']]);
});

it('you can get time sorted default desc logs files controller', function () {
    config(['log-viewer.defaults.file_sorting_method' => FolderSortingMethod::ModifiedTime]);

    $names = [
        'one.log',
        'two.log',
        'three.log',
    ];
    generateLogFiles($names, randomContent: true);
    array_map(function (string $name) {
        $this->travelTo(now()->addSecond());
        touch(storage_path('logs/'.$name), now()->timestamp);
    }, $names);

    $response = getJson(route('log-viewer.files'));

    expect(array_column($response->json(), 'name'))->toBe([
        'three.log',
        'two.log',
        'one.log',
    ]);
});

it('you can get time sorted desc logs files controller', function () {
    config(['log-viewer.defaults.file_sorting_method' => FolderSortingMethod::ModifiedTime]);

    $names = [
        'one.log',
        'two.log',
        'three.log',
    ];
    generateLogFiles($names, randomContent: true);
    array_map(function (string $name) {
        $this->travelTo(now()->addSecond());
        touch(storage_path('logs/'.$name), now()->timestamp);
    }, $names);

    $response = getJson(route('log-viewer.files', ['direction' => 'desc']));
    // dd($response->json());

    expect(array_column($response->json(), 'name'))->toBe([
        'three.log',
        'two.log',
        'one.log',
    ]);
});

it('you can get time sorted asc logs files controller', function () {
    config(['log-viewer.defaults.file_sorting_method' => FolderSortingMethod::ModifiedTime]);

    $names = [
        'one.log',
        'two.log',
        'three.log',
    ];
    generateLogFiles($names, randomContent: true);
    array_map(function (string $name) {
        $this->travelTo(now()->addSecond());
        touch(storage_path('logs/'.$name), now()->timestamp);
    }, $names);

    $response = getJson(route('log-viewer.files', ['direction' => 'asc']));

    expect(array_column($response->json(), 'name'))->toBe([
        'one.log',
        'two.log',
        'three.log',
    ]);
});
