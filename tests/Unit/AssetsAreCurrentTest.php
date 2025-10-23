<?php

use Illuminate\Support\Facades\File;
use Opcodes\LogViewer\Facades\LogViewer;

beforeEach(function () {
    // Ensure the source manifest exists
    File::ensureDirectoryExists(__DIR__.'/../../public');
    if (! File::exists(__DIR__.'/../../public/mix-manifest.json')) {
        File::put(__DIR__.'/../../public/mix-manifest.json', '{"test": "source"}');
    }
});

test('assetsAreCurrent returns true when published manifest matches source', function () {
    $publishedPath = public_path('vendor/log-viewer/mix-manifest.json');
    $sourcePath = __DIR__.'/../../public/mix-manifest.json';

    File::ensureDirectoryExists(dirname($publishedPath));
    File::copy($sourcePath, $publishedPath);

    expect(LogViewer::assetsAreCurrent())->toBeTrue();

    File::delete($publishedPath);
});

test('assetsAreCurrent returns false when published manifest differs from source', function () {
    $publishedPath = public_path('vendor/log-viewer/mix-manifest.json');

    File::ensureDirectoryExists(dirname($publishedPath));
    File::put($publishedPath, '{"test": "different"}');

    expect(LogViewer::assetsAreCurrent())->toBeFalse();

    File::delete($publishedPath);
});

test('assetsAreCurrent throws exception when published assets do not exist', function () {
    $publishedPath = public_path('vendor/log-viewer/mix-manifest.json');

    if (File::exists($publishedPath)) {
        File::delete($publishedPath);
    }

    LogViewer::assetsAreCurrent();
})->throws(
    RuntimeException::class,
    'Log Viewer assets are not published. Please run: php artisan vendor:publish --tag=log-viewer-assets --force'
);

test('assetsAreCurrent respects custom assets_path config', function () {
    $customPath = 'custom-log-viewer-path';
    config(['log-viewer.assets_path' => $customPath]);

    $publishedPath = public_path($customPath.'/mix-manifest.json');
    $sourcePath = __DIR__.'/../../public/mix-manifest.json';

    File::ensureDirectoryExists(dirname($publishedPath));
    File::copy($sourcePath, $publishedPath);

    expect(LogViewer::assetsAreCurrent())->toBeTrue();

    File::deleteDirectory(public_path($customPath));
});
