<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogFolder;

use function Pest\Laravel\get;

function assertCanDownloadFolder(string $folderName, string $expectedFileName): void
{
    $response = get(route('log-viewer.folders.request-download', $folderName));

    $response->assertOk();
    expect(URL::isValidUrl($response->json('url')))->toBeTrue();

    get($response->json('url'))
        ->assertOk()
        ->assertDownload($expectedFileName);
}

function assertCannotDownloadFolder(string $folderName): void
{
get(route('log-viewer.folders.request-download', $folderName))
->assertForbidden();
}

test('can download every folder by default', function () {
    generateLogFiles([$fileName = 'laravel.log']);
    $folder = LogViewer::getFolder('');

    assertCanDownloadFolder($folder->identifier, 'root.zip');
});

test('cannot download a folder that\'s not found', function () {
get(route('log-viewer.folders.request-download', 'notfound'))
->assertNotFound();
    });

test('"downloadLogFolder" gate can prevent folder download', function () {
    generateLogFiles([$fileName = 'laravel.log']);
    $folder = LogViewer::getFolder('');
    Gate::define('downloadLogFolder', fn (mixed $user) => false);

    assertCannotDownloadFolder($folder->identifier);

    // now let's allow access again
    Gate::define('downloadLogFolder', fn (mixed $user) => true);

    assertCanDownloadFolder($folder->identifier, 'root.zip');
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

    assertCanDownloadFolder($expectedFolder->identifier, 'root.zip');

    expect($gateChecked)->toBeTrue();
});
