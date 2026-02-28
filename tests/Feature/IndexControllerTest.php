<?php

use Illuminate\Support\Facades\File;
use function Pest\Laravel\get;

test('default per page options are passed to the view', function () {
    config()->set('log-viewer.per_page_options', [10, 25, 50, 100, 250, 500]);

    $response = get(route('log-viewer.index'));

    $response->assertStatus(200);
    $response->assertViewHas('logViewerScriptVariables');

    $scriptVars = $response->viewData('logViewerScriptVariables');
    expect($scriptVars)->toHaveKey('per_page_options');
    expect($scriptVars['per_page_options'])->toBe([10, 25, 50, 100, 250, 500]);
});

test('custom per page options are passed to the view', function () {
    config()->set('log-viewer.per_page_options', [5, 15, 30, 60, 120]);

    $response = get(route('log-viewer.index'));

    $response->assertStatus(200);
    $response->assertViewHas('logViewerScriptVariables');

    $scriptVars = $response->viewData('logViewerScriptVariables');
    expect($scriptVars)->toHaveKey('per_page_options');
    expect($scriptVars['per_page_options'])->toBe([5, 15, 30, 60, 120]);
});

test('per page options fallback to defaults if not set in config', function () {
    config()->set('log-viewer.per_page_options', null);

    $response = get(route('log-viewer.index'));

    $response->assertStatus(200);
    $response->assertViewHas('logViewerScriptVariables');

    $scriptVars = $response->viewData('logViewerScriptVariables');
    expect($scriptVars)->toHaveKey('per_page_options');
    expect($scriptVars['per_page_options'])->toBe([10, 25, 50, 100, 250, 500]);
});

test('view receives assetsPublished flag when assets are published', function () {
    $response = get(route('log-viewer.index'));

    $response->assertStatus(200);
    $response->assertViewHas('assetsPublished', true);
});

test('view receives assetsPublished flag as false when assets are not published', function () {
    // Remove the published assets
    $publishedPath = public_path(config('log-viewer.assets_path'));
    File::deleteDirectory($publishedPath);

    $response = get(route('log-viewer.index'));

    $response->assertStatus(200);
    $response->assertViewHas('assetsPublished', false);
});

test('assets_outdated is false when assets are not published', function () {
    // Remove the published assets
    $publishedPath = public_path(config('log-viewer.assets_path'));
    File::deleteDirectory($publishedPath);

    $response = get(route('log-viewer.index'));

    $response->assertStatus(200);
    $scriptVars = $response->viewData('logViewerScriptVariables');
    expect($scriptVars['assets_outdated'])->toBeFalse();
});

test('page loads successfully without published assets using inline mode', function () {
    // Remove the published assets
    $publishedPath = public_path(config('log-viewer.assets_path'));
    File::deleteDirectory($publishedPath);

    $response = get(route('log-viewer.index'));

    $response->assertStatus(200);
    $response->assertViewHas('assetsPublished', false);

    // Should contain inline style and script tags
    $content = $response->getContent();
    expect($content)->toContain('<style>');
    expect($content)->toContain('<script>');
    expect($content)->toContain('data:image/png;base64,');
});

test('page loads successfully with published assets using external file mode', function () {
    $response = get(route('log-viewer.index'));

    $response->assertStatus(200);
    $response->assertViewHas('assetsPublished', true);

    // Should contain external file references
    $content = $response->getContent();
    expect($content)->toContain('href="');
    expect($content)->toContain('src="');
    expect($content)->toContain(config('log-viewer.assets_path'));
});

test('assets_outdated is true when assets are published but stale', function () {
    // Overwrite the published manifest with different content
    $publishedPath = public_path(config('log-viewer.assets_path').'/mix-manifest.json');
    File::ensureDirectoryExists(dirname($publishedPath));
    File::put($publishedPath, '{"stale": true}');

    $response = get(route('log-viewer.index'));

    $response->assertStatus(200);
    $scriptVars = $response->viewData('logViewerScriptVariables');
    expect($scriptVars['assets_outdated'])->toBeTrue();
});

test('inline mode does not contain external asset references', function () {
    // Remove the published assets
    $publishedPath = public_path(config('log-viewer.assets_path'));
    File::deleteDirectory($publishedPath);

    $response = get(route('log-viewer.index'));
    $content = $response->getContent();

    // Should NOT contain external file references to the assets path
    expect($content)->not->toContain('vendor/log-viewer/app.css');
    expect($content)->not->toContain('vendor/log-viewer/app.js');
    // Should contain inline tags instead
    expect($content)->toContain('<style>');
});

test('published mode does not contain inline style or script blocks from assets', function () {
    $response = get(route('log-viewer.index'));
    $content = $response->getContent();

    // Should NOT contain inline <style> tags (the CSS should be external)
    expect($content)->not->toContain('<style>');
    // Should NOT contain base64 favicon
    expect($content)->not->toContain('data:image/png;base64,');
    // Should contain external references
    expect($content)->toContain(config('log-viewer.assets_path'));
});
