<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogFile;
use Opcodes\LogViewer\LogFolder;

test('can delete every folder by default', function () {
    generateLogFiles([$fileName = 'laravel.log']);

    Livewire::test('log-viewer::file-list')
        ->call('deleteFolder', '')
        ->assertOk();

    test()->assertFileDoesNotExist(storage_path('logs/'.$fileName));
});

test('deleting a folder that\'s not found still returns a successful response', function () {
    Livewire::test('log-viewer::file-list')
        ->call('deleteFolder', 'notfound')
        ->assertOk();
});

test('"deleteLogFolder" gate can prevent folder deletion', function () {
    generateLogFiles([$fileName = 'laravel.log']);
    Gate::define('deleteLogFolder', fn (mixed $user, ?LogFolder $folder = null) => false);

    Livewire::test('log-viewer::file-list')
        ->call('deleteFolder', '')
        ->assertForbidden();
    test()->assertFileExists(storage_path('logs/'.$fileName));

    // now let's allow access again
    Gate::define('deleteLogFolder', fn (mixed $user, ?LogFolder $folder = null) => true);

    Livewire::test('log-viewer::file-list')
        ->call('deleteFolder', '')
        ->assertOk();
    test()->assertFileDoesNotExist(storage_path('logs/'.$fileName));
});

test('"deleteLogFolder" gate is supplied with a log folder object', function () {
    generateLogFiles([$fileName = 'laravel.log']);
    $expectedFolder = LogViewer::getFolder($folderIdentifier = '');
    $gateChecked = false;

    //                                              we use "mixed" here because we don't have a real User object in our tests
    Gate::define('deleteLogFolder', function (mixed $user, LogFolder $folder) use ($expectedFolder, &$gateChecked) {
        expect($folder)->toBeInstanceOf(LogFolder::class)
            ->identifier->toBe($expectedFolder->identifier);
        $gateChecked = true;

        return true;
    });

    Livewire::test('log-viewer::file-list')
        ->call('deleteFolder', $folderIdentifier);
    test()->assertFileDoesNotExist(storage_path('logs/'.$fileName));

    expect($gateChecked)->toBeTrue();
});

test('individual file deletion gate is also checked before deleting the files', function () {
    generateLogFiles([$allowed = 'laravel.log', $notAllowed = 'forbidden.log']);
    $folder = LogViewer::getFolder('');
    Gate::define('deleteLogFile', fn (mixed $user, ?LogFile $file) => $file->name !== $notAllowed);

    Livewire::test('log-viewer::file-list')
        ->call('deleteFolder', '')
        ->assertOk();   // because deleting folders is allowed.

    test()->assertFileDoesNotExist(storage_path('logs/'.$allowed));
    test()->assertFileExists(storage_path('logs/'.$notAllowed));
});
