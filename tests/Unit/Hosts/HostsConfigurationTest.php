<?php

use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\Host;
use Opcodes\LogViewer\HostCollection;
use Opcodes\LogViewer\Utils\Utils;

it('can get a list of hosts from configuration', function () {
    config(['log-viewer.hosts' => $hostConfigs = [
        'local' => [
            'name' => 'Local',
            'host' => null,
        ],
        'first' => [
            'name' => 'First host',
            'host' => 'https://example.com/log-viewer',
            'headers' => [
                'Authorization' => 'Bearer 1234',
            ],
        ],
        [
            'name' => 'Second host',
            'host' => 'https://test.org/log-viewer-2',
        ],
    ]]);

    $hosts = LogViewer::getHosts();

    expect($hosts)->toBeInstanceOf(HostCollection::class)
        ->toHaveCount(count($hostConfigs));

    $first = $hosts->first();

    expect($first)->toBeInstanceOf(Host::class)
        ->and($first->identifier)->toBe('local')
        ->and($first->name)->toBe('Local')
        ->and($first->host)->toBe(null)
        ->and($first->headers)->toBe([]);

    $second = $hosts[1];

    expect($second)->toBeInstanceOf(Host::class)
        ->and($second->identifier)->toBe('first')
        ->and($second->name)->toBe($hostConfigs['first']['name'])
        ->and($second->host)->toBe($hostConfigs['first']['host'])
        ->and($second->headers)->toBe($hostConfigs['first']['headers']);

    $third = $hosts->last();

    expect($third)->toBeInstanceOf(Host::class)
        ->and($third->identifier)->toBe(Utils::shortMd5($hostConfigs[0]['host']))
        ->and($third->name)->toBe($hostConfigs[0]['name'])
        ->and($third->host)->toBe($hostConfigs[0]['host'])
        ->and($third->headers)->toBe([]);
});

it('can get an individual host by its identifier', function () {
    config(['log-viewer.hosts' => $hostConfigs = [
        'local' => [
            'name' => 'Local',
            'host' => null,
        ],
        'first' => [
            'name' => 'First host',
            'host' => 'https://example.com/log-viewer',
            'headers' => [
                'Authorization' => 'Bearer 1234',
            ],
        ],
        [
            'name' => 'Second host',
            'host' => 'https://test.org/log-viewer-2',
        ],
    ]]);

    $host = LogViewer::getHost('first');

    expect($host)->toBeInstanceOf(Host::class)
        ->and($host->identifier)->toBe('first')
        ->and($host->host)->toBe($hostConfigs['first']['host'])
        ->and($host->headers)->toBe($hostConfigs['first']['headers']);
});
