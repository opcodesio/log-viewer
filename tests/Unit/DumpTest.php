<?php

test('dumps')
    ->expect(['dump', 'dd'])
    ->toOnlyBeUsedIn('Opcodes\LogViewer\Utils\Benchmark');
