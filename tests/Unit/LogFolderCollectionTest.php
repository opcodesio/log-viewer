<?php

use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogFile;
use Opcodes\LogViewer\LogFileCollection;
use Opcodes\LogViewer\LogFolder;
use Opcodes\LogViewer\LogFolderCollection;

test('LogViewer::getFilesInFolders() returns a LogFolderCollection', function () {
    expect(LogViewer::getFilesGroupedByFolder())->toBeInstanceOf(LogFolderCollection::class);
});

test('Can be made from a LogFileCollection and grouped automatically', function () {
    // Let's assume we have a simple collection of log files from different folders.
    // When we try and turn that flat collection into a LogFolderCollection, it
    // could automatically group the logs into folders!
    $logFileCollection = new LogFileCollection([
        $debugFile1 = new LogFile('debug1.log', $debugFolder = 'debug'),
        $infoFile1 = new LogFile('info1.log', $infoFolder = 'info'),
        $debugFile2 = new Logfile('debug2.log', $debugFolder),
        $infoFile2 = new LogFile('info2.log', $infoFolder),
    ]);

    $folderCollection = LogFolderCollection::fromFiles($logFileCollection);

    expect($folderCollection)->toBeInstanceOf(LogFolderCollection::class)
        ->and($folderCollection->count())->toBe(2);

    // Let's double-check every entry in the collection.
    $firstFolder = $folderCollection[0];
    expect($firstFolder)->toBeInstanceOf(LogFolder::class)
        ->files()->toHaveCount(2)
        ->and($firstFolder->files()[0])->toBe($debugFile1)
        ->and($firstFolder->files()[1])->toBe($debugFile2);

    $secondFolder = $folderCollection[1];
    expect($secondFolder)->toBeInstanceOf(LogFolder::class)
        ->files()->toHaveCount(2)
        ->and($secondFolder->files()[0])->toBe($infoFile1)
        ->and($secondFolder->files()[1])->toBe($infoFile2);
});

test('LogFolderCollection can sort its folders by earliest logs first', function () {
    $firstFolder = mock(new LogFolder('folder', []))
        ->shouldReceive('earliestTimestamp')->andReturn(now()->subDay()->timestamp)->getMock();
    $secondFolder = mock(new LogFolder('folder2', []))
        ->shouldReceive('earliestTimestamp')->andReturn(now()->subDays(2)->timestamp)->getMock();
    $collection = new LogFolderCollection([$firstFolder, $secondFolder]);

    $collection->sortByEarliestFirst();

    expect($collection[0])->toBe($secondFolder)
        ->and($collection[1])->toBe($firstFolder);
});

test('LogFolderCollection can sort its folders by latest logs first', function () {
    $firstFolder = mock(new LogFolder('folder', []))
        ->shouldReceive('latestTimestamp')->andReturn(now()->subDays(2)->timestamp)->getMock();
    $secondFolder = mock(new LogFolder('folder2', []))
        ->shouldReceive('latestTimestamp')->andReturn(now()->subDay()->timestamp)->getMock();
    $collection = new LogFolderCollection([$firstFolder, $secondFolder]);

    $collection->sortByLatestFirst();

    expect($collection[0])->toBe($secondFolder)
        ->and($collection[1])->toBe($firstFolder);
});
