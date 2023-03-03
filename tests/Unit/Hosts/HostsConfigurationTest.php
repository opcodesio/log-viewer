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

it('hosts list can be empty', function () {
    config(['log-viewer.hosts' => []]);
    expect(LogViewer::getHosts())->toBeEmpty();
});

it('can provide a custom resolver for hosts', function () {
    $hostConfigs = [
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
    ];
    config(['log-viewer.hosts' => []]);
    expect(LogViewer::getHosts())->toBeEmpty();

    LogViewer::resolveHostsUsing(function () use ($hostConfigs) {
        return $hostConfigs;
    });

    $hosts = LogViewer::getHosts();

    expect($hosts)->toHaveCount(count($hostConfigs))
        ->and($hosts)->toBeInstanceOf(HostCollection::class);

    $firstHost = $hosts->get(0);

    expect($firstHost)->toBeInstanceOf(Host::class)
        ->and($firstHost->identifier)->toBe('local')
        ->and($firstHost->name)->toBe($hostConfigs['local']['name'])
        ->and($firstHost->host)->toBe($hostConfigs['local']['host']);

    $secondHost = $hosts->get(1);

    expect($secondHost)->toBeInstanceOf(Host::class)
        ->and($secondHost->identifier)->toBe('first')
        ->and($secondHost->name)->toBe($hostConfigs['first']['name'])
        ->and($secondHost->host)->toBe($hostConfigs['first']['host'])
        ->and($secondHost->headers)->toBe($hostConfigs['first']['headers']);
});

test('resolver callback is given the current collection of hosts', function () {
    $hasParam = false;
    LogViewer::resolveHostsUsing(function ($param) use (&$hasParam) {
        if (isset($param)) {
            $hasParam = true;
        }

        $this->assertInstanceOf(HostCollection::class, $param);

        return $param;
    });

    LogViewer::getHosts();

    $this->assertTrue($hasParam);
});

test('resolver can return a mix of Host instances and array configs as well', function () {
    LogViewer::resolveHostsUsing(function () {
        return [
            'local' => [
                'name' => 'Local',
                'host' => null,
            ],
            new Host('first', 'First host', 'https://example.com/log-viewer'),
        ];
    });

    $hosts = LogViewer::getHosts();

    expect($hosts)->toHaveCount(2)
        ->and($hosts)->toBeInstanceOf(HostCollection::class);

    $firstHost = $hosts->get(0);
    expect($firstHost)->toBeInstanceOf(Host::class)
        ->and($firstHost->identifier)->toBe('local')
        ->and($firstHost->name)->toBe('Local')
        ->and($firstHost->host)->toBe(null);

    $secondHost = $hosts->get(1);
    expect($secondHost)->toBeInstanceOf(Host::class)
        ->and($secondHost->identifier)->toBe('first')
        ->and($secondHost->name)->toBe('First host')
        ->and($secondHost->host)->toBe('https://example.com/log-viewer');
});
