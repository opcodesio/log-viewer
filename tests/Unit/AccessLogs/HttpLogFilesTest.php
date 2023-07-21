<?php

use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogFile;
use Opcodes\LogViewer\Logs\LogType;

it('can retrieve the http log files', function () {
    $access_dummy_path = __DIR__.'/Fixtures/access_dummy.log';
    $error_dummy_path = __DIR__.'/Fixtures/errors_dummy.log';
    $error_nginx_dummy_path = __DIR__.'/Fixtures/errors_nginx_dummy.log';

    config()->set('log-viewer.include_files', [__DIR__.'/Fixtures/*']);

    $files = LogViewer::getFiles();

    expect($files)->toHaveCount(3)

        ->and($files[0])->toBeInstanceOf(LogFile::class)
        ->and($files[0]->name)->toBe('errors_nginx_dummy.log')
        ->and($files[0]->type())->toBe(LogType::HTTP_ERROR_NGINX)
        ->and($files[0]->path)->toBe($error_nginx_dummy_path)
        ->and($files[0]->size())->toBe(filesize($error_nginx_dummy_path))

        ->and($files[1])->toBeInstanceOf(LogFile::class)
        ->and($files[1]->name)->toBe('errors_dummy.log')
        ->and($files[1]->type())->toBe(LogType::HTTP_ERROR_APACHE)
        ->and($files[1]->path)->toBe($error_dummy_path)
        ->and($files[1]->size())->toBe(filesize($error_dummy_path))

        ->and($files[2])->toBeInstanceOf(LogFile::class)
        ->and($files[2]->name)->toBe('access_dummy.log')
        ->and($files[2]->type())->toBe(LogType::HTTP_ACCESS)
        ->and($files[2]->path)->toBe($access_dummy_path)
        ->and($files[2]->size())->toBe(filesize($access_dummy_path));
});
