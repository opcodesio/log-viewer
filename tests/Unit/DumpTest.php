<?php

test('dumps', function () {
    if (version_compare(\Pest\version(), '2.0.0', '<')) {
        $this->markTestSkipped('This test is only for Pest 2.0.0+');
    }

    expect(['dump', 'dd'])
        ->toOnlyBeUsedIn('Opcodes\LogViewer\Utils\Benchmark');
});
