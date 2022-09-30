<?php

use Illuminate\Support\Facades\Gate;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogFolder;
use function Pest\Laravel\get;

test('can download every folder by default', function () {
    generateLogFiles([$fileName = 'laravel.log']);
    $folder = LogViewer::getFolder('');

    get(route('blv.download-folder', $folder->identifier))
        ->assertOk()
        ->assertDownload('root.zip');
});

test('cannot download a folder that\'s not found', function () {
    get(route('blv.download-folder', 'notfound'))
        ->assertNotFound();
});

test('"downloadLogFolder" gate can prevent folder download', function () {
    generateLogFiles([$fileName = 'laravel.log']);
    $folder = LogViewer::getFolder('');
    Gate::define('downloadLogFolder', fn (mixed $user) => false);

    get(route('blv.download-folder', $folder->identifier))
        ->assertForbidden();

    // now let's allow access again
    Gate::define('downloadLogFolder', fn (mixed $user) => true);

    get(route('blv.download-folder', $folder->identifier))
        ->assertOk()
        ->assertDownload('root.zip');
});

test('"downloadLogFolder" gate is supplied with a log folder object', function () {
    generateLogFiles([$fileName = 'laravel.log']);
    $expectedFolder = LogViewer::getFolder('');
    $gateChecked = false;

    Gate::define('downloadLogFolder', function (mixed $user, LogFolder $folder) use ($expectedFolder, &$gateChecked) {
        expect($folder)->toBeInstanceOf(LogFolder::class)
            ->identifier->toBe($expectedFolder->identifier);
        $gateChecked = true;

        return true;
    });

    get(route('blv.download-folder', $expectedFolder->identifier))
        ->assertOk()
        ->assertDownload('root.zip');

    expect($gateChecked)->toBeTrue();
});
