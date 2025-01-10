<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Opcodes\LogViewer\LogFile;

use function Pest\Laravel\get;

function assertCanDownloadFile(string $fileName): void
{
    $response = get(route('log-viewer.files.request-download', $fileName));

    $response->assertOk();
    expect(URL::isValidUrl($response->json('url')))->toBeTrue();

    get($response->json('url'))
        ->assertOk()
        ->assertDownload($fileName);
}

function assertCannotDownloadFile(string $fileName): void
{
get(route('log-viewer.files.request-download', $fileName))
->assertForbidden();
}

test('can download every file by default', function () {
    generateLogFiles([$fileName = 'laravel.log']);

    assertCanDownloadFile($fileName);
});

test('cannot download a file that\'s not found', function () {
get(route('log-viewer.files.request-download', 'notfound.log'))
->assertNotFound();
    });

test('"downloadLogFile" gate can prevent file download', function () {
    generateLogFiles([$fileName = 'laravel.log']);
    Gate::define('downloadLogFile', fn (mixed $user) => false);

    assertCannotDownloadFile($fileName);

    // now let's allow access again
    Gate::define('downloadLogFile', fn (mixed $user) => true);

    assertCanDownloadFile($fileName);
});

test('"downloadLogFile" gate is supplied with a log file object', function () {
    generateLogFiles([$fileName = 'laravel.log']);
    $gateChecked = false;

    Gate::define('downloadLogFile', function (mixed $user, LogFile $file) use ($fileName, &$gateChecked) {
        expect($file)->toBeInstanceOf(LogFile::class)
            ->name->toBe($fileName);
        $gateChecked = true;

        return true;
    });

    assertCanDownloadFile($fileName);

    expect($gateChecked)->toBeTrue();
});
