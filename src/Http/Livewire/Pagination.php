<?php

namespace Arukompas\BetterLogViewer\Http\Livewire;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Livewire\Component;

class Pagination extends Component
{
    public array $meta;
    public array $links;

    public function mount(AnonymousResourceCollection $collection)
    {
        // $this->meta = $collection->;
    }

    public function render()
    {
        return view('better-log-viewer::livewire.pagination');
    }
}
