<?php

namespace Opcodes\LogViewer\Http\Livewire;

use Livewire\Component;

class ThemeSwitcher extends Component
{
    public string $theme = '';

    public function mount()
    {
        $this->theme = session()->get('log-viewer:theme', 'system');
    }

    public function updatedTheme($theme)
    {
        session()->put('log-viewer:theme', $theme);
        $this->dispatchBrowserEvent('theme-updated');
    }

    public function render()
    {
        return view('log-viewer::livewire.theme-switcher');
    }
}
