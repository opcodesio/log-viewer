<?php

use function Pest\Laravel\get;

it('properly displays & configure back label', function () {
    get(route('blv.index'))->assertSeeText('Back to Laravel');

    $label = 'My Cool App';

    config()->set('log-viewer.back_to_system_label', $label);

    get(route('blv.index'))->assertSeeText($label);
});

test('home page loads Livewire component', function ($livewireComponent) {
    get(route('blv.index'))->assertSeeLivewire($livewireComponent);
})->with([
    'log-viewer::log-list',
    'log-viewer::file-list',
]);
