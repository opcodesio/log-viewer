<?php

use Illuminate\Support\Facades\Gate;
use Opcodes\LogViewer\LogFile;
use function Pest\Laravel\get;

test('can download every file by default', function () {
    generateLogFiles([$fileName = 'laravel.log']);

    get(route('blv.download-file', $fileName))
        ->assertOk()
        ->assertDownload($fileName);
});

test('cannot download a file that\'s not found', function () {
    get(route('blv.download-file', 'notfound.log'))
        ->assertNotFound();
});

test('"downloadLogFile" gate can prevent file download', function () {
    generateLogFiles([$fileName = 'laravel.log']);
    Gate::define('downloadLogFile', fn (mixed $user) => false);

    get(route('blv.download-file', $fileName))
        ->assertForbidden();

    // now let's allow access again
    Gate::define('downloadLogFile', fn (mixed $user) => true);

    get(route('blv.download-file', $fileName))
        ->assertOk()
        ->assertDownload($fileName);
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

    get(route('blv.download-file', $fileName))
        ->assertOk()
        ->assertDownload($fileName);

    expect($gateChecked)->toBeTrue();
});
