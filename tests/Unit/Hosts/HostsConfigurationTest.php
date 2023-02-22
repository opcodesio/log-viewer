<?php

use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\Host;
use Opcodes\LogViewer\HostCollection;
use Opcodes\LogViewer\Utils\Utils;

it('can get a list of hosts from configuration', function () {
    config(['log-viewer.hosts' => $hostConfigs = [
        [
            'host' => 'https://example.com/log-viewer',
            'headers' => [
                'Authorization' => 'Bearer 1234',
            ],
        ],
        [
            'host' => 'https://test.org/log-viewer-2',
        ],
    ]]);

    $hosts = LogViewer::getHosts();

    expect($hosts)->toBeInstanceOf(HostCollection::class)
        ->toHaveCount(count($hostConfigs));

    $first = $hosts->first();

    expect($first)->toBeInstanceOf(Host::class)
        ->and($first->identifier)->toBe(Utils::shortMd5($hostConfigs[0]['host']))
        ->and($first->host)->toBe($hostConfigs[0]['host'])
        ->and($first->headers)->toBe($hostConfigs[0]['headers']);

    $second = $hosts->last();

    expect($second)->toBeInstanceOf(Host::class)
        ->and($second->identifier)->toBe(Utils::shortMd5($hostConfigs[1]['host']))
        ->and($second->host)->toBe($hostConfigs[1]['host'])
        ->and($second->headers)->toBe([]);
});

it('can get an individual host by its identifier', function () {
    config(['log-viewer.hosts' => $hostConfigs = [
        [
            'host' => 'https://example.com/log-viewer',
            'headers' => [
                'Authorization' => 'Bearer 1234',
            ],
        ],
        [
            'host' => 'https://test.org/log-viewer-2',
        ],
    ]]);
    $secondHostIdentifier = Utils::shortMd5($hostConfigs[1]['host']);

    $host = LogViewer::getHost($secondHostIdentifier);

    expect($host)->toBeInstanceOf(Host::class)
        ->and($host->identifier)->toBe($secondHostIdentifier)
        ->and($host->host)->toBe($hostConfigs[1]['host'])
        ->and($host->headers)->toBe([]);
});
