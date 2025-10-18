<?php

use Opcodes\LogViewer\Enums\SortingMethod;
use Opcodes\LogViewer\Enums\SortingOrder;
use Opcodes\LogViewer\LogFolder;

use function Pest\Laravel\getJson;

beforeEach(function () {
    config(['log-viewer.include_files' => ['*/**.log']]);
});

it('can get the log files', function () {
    config(['log-viewer.defaults.folder_sorting_method' => SortingMethod::ModifiedTime]);
    config(['log-viewer.defaults.folder_sorting_order' => SortingOrder::Descending]);

    generateLogFiles([
        'one/1.one.log',
        'one/2.two.log',
        'two/3.three.log',
    ], randomContent: true);

    $response = getJson(route('log-viewer.folders'));

    expect($response->json())->not->toHaveKey('data');
    $response->assertJsonCount(2)
        ->assertJsonFragment(['clean_path' => LogFolder::rootPrefix().DIRECTORY_SEPARATOR.'one'])
        ->assertJsonFragment(['clean_path' => LogFolder::rootPrefix().DIRECTORY_SEPARATOR.'two']);
});

it('folders are sorted alphabetically descending when configured', function () {
    config(['log-viewer.include_files' => ['*.log', '*/**.log']]);
    config(['log-viewer.defaults.folder_sorting_method' => SortingMethod::Alphabetical]);
    config(['log-viewer.defaults.folder_sorting_order' => SortingOrder::Ascending]);

    generateLogFiles([
        'one/1.one.log',
        'one/2.two.log',
        'two/3.three.log',
        'alpha/4.alpha.log',
        'laravel.log',
    ], randomContent: true);

    $response = getJson(route('log-viewer.folders'));
    $folders = $response->json();
    // Should be sorted: 'root', 'alpha', 'one', 'two'
    $response->assertJsonCount(4);
    expect($folders[0]['clean_path'])->toBe(LogFolder::rootPrefix());
    expect($folders[1]['clean_path'])->toBe(LogFolder::rootPrefix().DIRECTORY_SEPARATOR.'alpha');
    expect($folders[2]['clean_path'])->toBe(LogFolder::rootPrefix().DIRECTORY_SEPARATOR.'one');
    expect($folders[3]['clean_path'])->toBe(LogFolder::rootPrefix().DIRECTORY_SEPARATOR.'two');
});
