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
    $firstFolder = Mockery::mock(new LogFolder('folder', []))
        ->allows(['earliestTimestamp' => now()->subDay()->timestamp]);
    $secondFolder = Mockery::mock(new LogFolder('folder2', []))
        ->allows(['earliestTimestamp' => now()->subDays(2)->timestamp]);
    $collection = new LogFolderCollection([$firstFolder, $secondFolder]);

    $collection->sortByEarliestFirst();

    expect($collection[0])->toBe($secondFolder)
        ->and($collection[1])->toBe($firstFolder);
});

test('LogFolderCollection can sort its folders by latest logs first', function () {
    $firstFolder = Mockery::mock(new LogFolder('folder', []))
        ->allows(['latestTimestamp' => now()->subDays(2)->timestamp]);
    $secondFolder = Mockery::mock(new LogFolder('folder2', []))
        ->allows(['latestTimestamp' => now()->subDay()->timestamp]);
    $collection = new LogFolderCollection([$firstFolder, $secondFolder]);

    $collection->sortByLatestFirst();

    expect($collection[0])->toBe($secondFolder)
        ->and($collection[1])->toBe($firstFolder);
});

test('LogFolderCollection can sort its folders by earliest first, including its files', function () {
    $firstFile = Mockery::mock(new LogFile('test.log', 'test.log'))
        ->allows(['earliestTimestamp' => now()->subDay()->timestamp]);
    $secondFile = Mockery::mock(new LogFile('test2.log', 'test2.log'))
        ->allows(['earliestTimestamp' => now()->subDays(2)->timestamp]);

    $dummyFolder = Mockery::mock(new LogFolder('folder2', []))
        ->allows(['earliestTimestamp' => now()->subDay()->timestamp]);
    $folderWithFiles = new LogFolder('folder', [$firstFile, $secondFile]);

    $collection = new LogFolderCollection([$dummyFolder, $folderWithFiles]);

    // So, from the setup above, we know that $folderWithFiles should come first,
    // because it contains a file that has an earlier timestamp.
    // The $folderWithFiles folder's files, though, should have the second file first,
    // because that's earlier as well.

    $collection->sortByEarliestFirstIncludingFiles();

    expect($collection[0])->toBe($folderWithFiles)
        ->and($collection[1])->toBe($dummyFolder);

    $folderFiles = $collection[0]->files();
    expect($folderFiles[0])->toBe($secondFile)
        ->and($folderFiles[1])->toBe($firstFile);
});

test('LogFolderCollection can sort its folders by latest first, including its files', function () {
    $firstFile = Mockery::mock(new LogFile('test.log', 'test.log'))
        ->allows(['latestTimestamp' => now()->subDays(2)->timestamp]);
    $secondFile = Mockery::mock(new LogFile('test2.log', 'test2.log'))
        ->allows(['latestTimestamp' => now()->subDay()->timestamp]);

    $dummyFolder = Mockery::mock(new LogFolder('folder2', []))
        ->allows(['latestTimestamp' => now()->subDays(2)->timestamp]);
    $folderWithFiles = new LogFolder('folder', [$firstFile, $secondFile]);

    $collection = new LogFolderCollection([$dummyFolder, $folderWithFiles]);

    // So, from the setup above, we know that $folderWithFiles should come first,
    // because it contains a file that has a later timestamp.
    // The $folderWithFiles folder's files, though, should have the second file first,
    // because that's later as well.

    $collection->sortByLatestFirstIncludingFiles();

    expect($collection[0])->toBe($folderWithFiles)
        ->and($collection[1])->toBe($dummyFolder);

    $folderFiles = $collection[0]->files();
    expect($folderFiles[0])->toBe($secondFile)
        ->and($folderFiles[1])->toBe($firstFile);
});
