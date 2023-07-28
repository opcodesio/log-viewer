<?php

use Illuminate\Support\Facades\File;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogFile;
use Opcodes\LogViewer\Logs\LogType;

beforeEach(function () {
    File::makeDirectory(storage_path('logs/http'), 0755, true);
    $slash = DIRECTORY_SEPARATOR;
    File::put(
        $this->access_dummy_log_path = storage_path("logs${slash}http${slash}access_dummy.log"),
        file_get_contents(__DIR__.'/Fixtures/access_dummy.log')
    );
    File::put(
        $this->error_dummy_log_path = storage_path("logs${slash}http${slash}errors_dummy.log"),
        file_get_contents(__DIR__.'/Fixtures/errors_dummy.log')
    );
    File::put(
        $this->error_nginx_dummy_log_path = storage_path("logs${slash}http${slash}errors_nginx_dummy.log"),
        file_get_contents(__DIR__.'/Fixtures/errors_nginx_dummy.log')
    );

});

it('can retrieve the http log files', function () {
    config(['log-viewer.include_files' => ['http/*.log']]);

    $files = LogViewer::getFiles();

    expect($files)->toHaveCount(3)

        ->and($files[0])->toBeInstanceOf(LogFile::class)
        ->and($files[0]->name)->toBe('errors_nginx_dummy.log')
        ->and($files[0]->type()->value)->toBe(LogType::HTTP_ERROR_NGINX)
        ->and($files[0]->path)->toBe($this->error_nginx_dummy_log_path)
        ->and($files[0]->size())->toBe(filesize($this->error_nginx_dummy_log_path))

        ->and($files[1])->toBeInstanceOf(LogFile::class)
        ->and($files[1]->name)->toBe('errors_dummy.log')
        ->and($files[1]->type()->value)->toBe(LogType::HTTP_ERROR_APACHE)
        ->and($files[1]->path)->toBe($this->error_dummy_log_path)
        ->and($files[1]->size())->toBe(filesize($this->error_dummy_log_path))

        ->and($files[2])->toBeInstanceOf(LogFile::class)
        ->and($files[2]->name)->toBe('access_dummy.log')
        ->and($files[2]->type()->value)->toBe(LogType::HTTP_ACCESS)
        ->and($files[2]->path)->toBe($this->access_dummy_log_path)
        ->and($files[2]->size())->toBe(filesize($this->access_dummy_log_path));
});
