<?php

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
