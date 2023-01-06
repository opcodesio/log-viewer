<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Opcodes\LogViewer\LogFile;

test('can delete every file by default', function () {
    generateLogFiles([$fileName = 'laravel.log']);

    Livewire::test('log-viewer::file-list')
        ->call('deleteFile', $fileName)
        ->assertOk();

    test()->assertFileDoesNotExist(storage_path('logs/'.$fileName));
});

test('deleting a file that\'s not found still returns a successful response', function () {
    Livewire::test('log-viewer::file-list')
        ->call('deleteFile', 'notfound.log')
        ->assertOk();
});

test('"deleteLogFile" gate can prevent file deletion', function () {
    generateLogFiles([$fileName = 'laravel.log']);
    Gate::define('deleteLogFile', fn (mixed $user, ?LogFile $file = null) => false);

    Livewire::test('log-viewer::file-list')
        ->call('deleteFile', $fileName)
        ->assertForbidden();
    test()->assertFileExists(storage_path('logs/'.$fileName));

    // now let's allow access again
    Gate::define('deleteLogFile', fn (mixed $user, ?LogFile $file = null) => true);

    Livewire::test('log-viewer::file-list')
        ->call('deleteFile', $fileName)
        ->assertOk();
    test()->assertFileDoesNotExist(storage_path('logs/'.$fileName));
});

test('"deleteLogFile" gate is supplied with a log file object', function () {
    generateLogFiles([$fileName = 'laravel.log']);
    $gateChecked = false;

    //                                              we use "mixed" here because we don't have a real User object in our tests
    Gate::define('deleteLogFile', function (mixed $user, LogFile $file) use ($fileName, &$gateChecked) {
        expect($file)->toBeInstanceOf(LogFile::class)
            ->name->toBe($fileName);
        $gateChecked = true;

        return true;
    });

    Livewire::test('log-viewer::file-list')
        ->call('deleteFile', $fileName);
    test()->assertFileDoesNotExist(storage_path('logs/'.$fileName));

    expect($gateChecked)->toBeTrue();
});
