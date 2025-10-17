<?php

use Opcodes\LogViewer\Enums\SortingMethod;
use Opcodes\LogViewer\Enums\SortingOrder;

use function Pest\Laravel\getJson;

beforeEach(function () {
    config(['log-viewer.include_files' => ['*.log', '*/**.log']]);
});

// FilesController with invalid sorting values

it('files controller returns default desc order when invalid direction is provided', function () {
    config(['log-viewer.defaults.file_sorting_method' => SortingMethod::ModifiedTime]);
    $names = ['one.log', 'two.log', 'three.log'];
    generateLogFiles($names, randomContent: true);

    array_map(function (string $name) {
        $this->travelTo(now()->addSecond());
        touch(storage_path('logs/'.$name), now()->timestamp);
    }, $names);

    $response = getJson(route('log-viewer.files', ['direction' => 'invalid']));

    expect(array_column($response->json(), 'name'))->toBe([
        'three.log',
        'two.log',
        'one.log',
    ]);
});

it('files controller returns default desc order when empty direction is provided', function () {
    config(['log-viewer.defaults.file_sorting_method' => SortingMethod::ModifiedTime]);
    $names = ['one.log', 'two.log', 'three.log'];
    generateLogFiles($names, randomContent: true);

    array_map(function (string $name) {
        $this->travelTo(now()->addSecond());
        touch(storage_path('logs/'.$name), now()->timestamp);
    }, $names);

    $response = getJson(route('log-viewer.files', ['direction' => '']));

    expect(array_column($response->json(), 'name'))->toBe([
        'three.log',
        'two.log',
        'one.log',
    ]);
});

it('files controller returns default desc order when direction is null', function () {
    config(['log-viewer.defaults.file_sorting_method' => SortingMethod::ModifiedTime]);
    $names = ['one.log', 'two.log', 'three.log'];
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

it('files controller returns alphabetical sorting with default desc when invalid direction is provided', function () {
    config(['log-viewer.defaults.file_sorting_method' => SortingMethod::Alphabetical]);
    generateLogFiles(['one.log', 'two.log', 'three.log', 'four.log'], randomContent: true);

    $response = getJson(route('log-viewer.files', ['direction' => 'invalid']));

    expect(array_column($response->json(), 'name'))->toBe([
        'two.log',
        'three.log',
        'one.log',
        'four.log',
    ]);
});

it('files controller returns correct order with valid asc direction and alphabetical sorting', function () {
    config(['log-viewer.defaults.file_sorting_method' => SortingMethod::Alphabetical]);
    generateLogFiles(['one.log', 'two.log', 'three.log', 'four.log'], randomContent: true);

    $response = getJson(route('log-viewer.files', ['direction' => SortingOrder::Ascending]));

    expect(array_column($response->json(), 'name'))->toBe([
        'four.log',
        'one.log',
        'three.log',
        'two.log',
    ]);
});

// FoldersController with invalid sorting values

it('folders controller returns default desc order when invalid direction is provided', function () {
    config(['log-viewer.include_files' => ['*/**.log']]);
    config(['log-viewer.defaults.folder_sorting_method' => SortingMethod::Alphabetical]);
    config(['log-viewer.defaults.file_sorting_method' => SortingMethod::ModifiedTime]);
    $names = ['sub/one.log', 'sub/two.log', 'sub/three.log'];
    generateLogFiles($names, randomContent: true);

    array_map(function (string $name) {
        $this->travelTo(now()->addSecond());
        touch(storage_path('logs/'.$name), now()->timestamp);
    }, $names);

    $response = getJson(route('log-viewer.folders', ['direction' => 'invalid']));

    expect(array_column($response->json()[0]['files'], 'name'))->toBe([
        'three.log',
        'two.log',
        'one.log',
    ]);
});

it('folders controller returns default desc order when empty direction is provided', function () {
    config(['log-viewer.include_files' => ['*/**.log']]);
    config(['log-viewer.defaults.folder_sorting_method' => SortingMethod::Alphabetical]);
    config(['log-viewer.defaults.file_sorting_method' => SortingMethod::ModifiedTime]);
    $names = ['sub/one.log', 'sub/two.log', 'sub/three.log'];
    generateLogFiles($names, randomContent: true);

    array_map(function (string $name) {
        $this->travelTo(now()->addSecond());
        touch(storage_path('logs/'.$name), now()->timestamp);
    }, $names);

    $response = getJson(route('log-viewer.folders', ['direction' => '']));

    expect(array_column($response->json()[0]['files'], 'name'))->toBe([
        'three.log',
        'two.log',
        'one.log',
    ]);
});

it('folders controller returns default desc order when direction is null', function () {
    config(['log-viewer.include_files' => ['*/**.log']]);
    config(['log-viewer.defaults.folder_sorting_method' => SortingMethod::Alphabetical]);
    config(['log-viewer.defaults.file_sorting_method' => SortingMethod::ModifiedTime]);
    $names = ['sub/one.log', 'sub/two.log', 'sub/three.log'];
    generateLogFiles($names, randomContent: true);

    array_map(function (string $name) {
        $this->travelTo(now()->addSecond());
        touch(storage_path('logs/'.$name), now()->timestamp);
    }, $names);

    $response = getJson(route('log-viewer.folders'));

    expect(array_column($response->json()[0]['files'], 'name'))->toBe([
        'three.log',
        'two.log',
        'one.log',
    ]);
});

it('folders controller returns correct order with valid asc direction and alphabetical sorting', function () {
    config(['log-viewer.include_files' => ['*/**.log']]);
    config(['log-viewer.defaults.folder_sorting_method' => SortingMethod::Alphabetical]);
    config(['log-viewer.defaults.file_sorting_method' => SortingMethod::Alphabetical]);
    generateLogFiles(['sub/one.log', 'sub/two.log', 'sub/three.log', 'sub/four.log'], randomContent: true);

    $response = getJson(route('log-viewer.folders', ['direction' => SortingOrder::Ascending]));

    expect(array_column($response->json()[0]['files'], 'name'))->toBe([
        'four.log',
        'one.log',
        'three.log',
        'two.log',
    ]);
});
