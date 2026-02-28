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

test('assetsAreCurrent returns false when published assets do not exist', function () {
    $publishedPath = public_path('vendor/log-viewer/mix-manifest.json');

    if (File::exists($publishedPath)) {
        File::delete($publishedPath);
    }

    expect(LogViewer::assetsAreCurrent())->toBeFalse();
});

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

test('assetsArePublished returns true when published manifest exists', function () {
    $publishedPath = public_path('vendor/log-viewer/mix-manifest.json');

    File::ensureDirectoryExists(dirname($publishedPath));
    File::put($publishedPath, '{"test": "source"}');

    expect(LogViewer::assetsArePublished())->toBeTrue();

    File::delete($publishedPath);
});

test('assetsArePublished returns false when published manifest does not exist', function () {
    $publishedPath = public_path('vendor/log-viewer/mix-manifest.json');

    if (File::exists($publishedPath)) {
        File::delete($publishedPath);
    }

    expect(LogViewer::assetsArePublished())->toBeFalse();
});

test('assetsArePublished respects custom assets_path config', function () {
    $customPath = 'custom-assets-path';
    config(['log-viewer.assets_path' => $customPath]);

    $publishedPath = public_path($customPath.'/mix-manifest.json');

    File::ensureDirectoryExists(dirname($publishedPath));
    File::put($publishedPath, '{"test": "source"}');

    expect(LogViewer::assetsArePublished())->toBeTrue();

    File::deleteDirectory(public_path($customPath));
});

test('css() returns an HtmlString containing a style tag', function () {
    $result = LogViewer::css();

    expect($result)->toBeInstanceOf(\Illuminate\Support\HtmlString::class);
    expect((string) $result)->toStartWith('<style>');
    expect((string) $result)->toEndWith('</style>');
    expect(strlen((string) $result))->toBeGreaterThan(100);
});

test('js() returns an HtmlString containing a script tag', function () {
    $result = LogViewer::js();

    expect($result)->toBeInstanceOf(\Illuminate\Support\HtmlString::class);
    expect((string) $result)->toStartWith('<script>');
    expect((string) $result)->toEndWith('</script>');
    expect(strlen((string) $result))->toBeGreaterThan(100);
});

test('favicon() returns an HtmlString containing a base64 data URI', function () {
    $result = LogViewer::favicon();

    expect($result)->toBeInstanceOf(\Illuminate\Support\HtmlString::class);
    expect((string) $result)->toContain('data:image/png;base64,');
    expect((string) $result)->toStartWith('<link rel="shortcut icon"');
    expect((string) $result)->toEndWith('">');
});
