<div x-data="dropdown"
    x-on:keydown.escape.prevent.stop="close($refs.button)"
    x-on:focusin.window="! $refs.panel.contains($event.target) && close()"
    x-id="['dropdown-button']"
    class="relative"
>
    <button type="button" class="menu-button"
            x-ref="button" x-on:click.stop="toggle()" :aria-expanded="open" :aria-controls="$id('dropdown-button')">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-cog" /></svg>
    </button>

    <div
        x-ref="panel"
        x-show="open"
        x-bind="transitions"
        x-on:click.outside="close($refs.button)"
        :id="$id('dropdown-button')"
        style="min-width: 250px;"
        class="dropdown"
    >
        <div class="py-2">
            <div class="label">Settings</div>

            <button wire:click="toggleShorterStackTraces">
                <x-log-viewer::checkmark :checked="$shorterStackTraces" />
                <span class="ml-3">Shorter stack traces</span>
            </button>

            <div class="divider"></div>
            <div class="label">Actions</div>

            <button wire:click="clearCacheAll" x-data="{ cacheRecentlyCleared: @json($cacheRecentlyCleared) }" x-init="setTimeout(() => cacheRecentlyCleared = false, 2000)">
                <svg xmlns="http://www.w3.org/2000/svg" wire:loading.class="hidden" wire:target="clearCacheAll" fill="currentColor"><use href="#icon-database" /></svg>
                <svg xmlns="http://www.w3.org/2000/svg" wire:loading.class.remove="hidden" wire:target="clearCacheAll" class="hidden spin" fill="currentColor"><use href="#icon-spinner" /></svg>
                <span x-show="!cacheRecentlyCleared" wire:loading.class="hidden" wire:target="clearCacheAll">Clear indices for all files</span>
                <span x-show="!cacheRecentlyCleared" wire:loading wire:target="clearCacheAll">Please wait...</span>
                <span x-show="cacheRecentlyCleared" class="text-emerald-500">File indices cleared</span>
            </button>

            <button x-data="{ copied: false }" x-clipboard="window.location.href" x-on:click.stop="copied = true; setTimeout(() => copied = false, 2000)">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-share" /></svg>
                <span x-show="!copied">Share this page</span>
                <span x-show="copied" class="text-emerald-500">Link copied!</span>
            </button>

            <div class="divider"></div>

            <button x-on:click="$store.logViewer.toggleTheme()">
                <svg x-show="$store.logViewer.theme === 'System'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-theme-auto" /></svg>
                <svg x-show="$store.logViewer.theme === 'Light'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-theme-light" /></svg>
                <svg x-show="$store.logViewer.theme === 'Dark'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-theme-dark" /></svg>
                <span>Theme: <span x-html="$store.logViewer.theme" class="font-semibold"></span></span>
            </button>

            <a href="https://www.github.com/opcodesio/log-viewer" target="_blank">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-question" /></svg>
                Help
            </a>
        </div>
    </div>
</div>
