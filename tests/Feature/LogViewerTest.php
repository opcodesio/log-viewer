<?php

use Opcodes\LogViewer\Facades\LogViewer;

use function PHPUnit\Framework\assertContains;
use function PHPUnit\Framework\assertNotContains;

beforeEach(function () {
    generateLogFiles(['laravel.log', 'other.log'], randomContent: true, type: 'laravel');
});

it('properly includes log files', function () {
    $fileNames = LogViewer::getFiles()->map->name;

    assertContains('laravel.log', $fileNames);
    assertContains('other.log', $fileNames);
});

it('properly excludes log files', function () {
    config()->set('log-viewer.exclude_files', ['*other*']);

    $fileNames = LogViewer::getFiles()->map->name;

    assertContains('laravel.log', $fileNames);
    assertNotContains('other.log', $fileNames);
});

it('hides unknown log files', function () {
    config()->set('log-viewer.hide_unknown_files', true);
    $unknownFile = generateLogFile('unknown.log', content: 'unknown log content');

    $fileNames = LogViewer::getFiles()->map->name;

    assertNotContains($unknownFile->name, $fileNames);
    assertContains('laravel.log', $fileNames);
    assertContains('other.log', $fileNames);
});

it('can get the timezone', function () {
    config()->set('log-viewer.timezone', 'Europe/Vilnius');

    expect(LogViewer::timezone())->toBe('Europe/Vilnius');
});

it('defaults to the app timezone', function () {
    config()->set('app.timezone', 'Europe/Vilnius');

    expect(LogViewer::timezone())->toBe('Europe/Vilnius');
});

it('defaults to UTC if no timezone is set anywhere', function () {
    config()->set('app.timezone', null);
    config()->set('log-viewer.timezone', null);

    expect(LogViewer::timezone())->toBe('UTC');
});
