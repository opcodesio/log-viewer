<div x-data="{
        open: false,
        toggle() {
            if (this.open) { return this.close() }
            this.$refs.button.focus()
            this.open = true
        },
        close(focusAfter) {
            if (! this.open) { return }
            this.open = false
            focusAfter && focusAfter.focus()
        }
    }"
    x-on:keydown.escape.prevent.stop="close($refs.button)"
    x-on:focusin.window="! $refs.panel.contains($event.target) && close()"
    x-id="['dropdown-button']"
>
    <button type="button" class="relative p-2 text-gray-400 group hover:text-gray-500 rounded-md outline-emerald-500 cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500 transition duration-200"
            x-ref="button" x-on:click.stop="toggle()" :aria-expanded="open" :aria-controls="$id('dropdown-button')"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-cog" /></svg>
    </button>

    <div
        x-ref="panel"
        x-show="open"
        x-transition.origin.top.right
        x-on:click.outside="close($refs.button)"
        :id="$id('dropdown-button')"
        style="display: none;"
        class="dropdown"
    >
        <div class="py-2">
            <div class="label">Settings</div>
{{--            <button wire:click="toggleAutomaticRefresh">--}}
{{--                <x-log-viewer::checkmark :checked="$refreshAutomatically" />--}}
{{--                <span class="ml-3">Refresh every 2 seconds</span>--}}
{{--            </button>--}}

            <button wire:click="toggleShorterStackTraces">
                <x-log-viewer::checkmark :checked="$shorterStackTraces" />
                <span class="ml-3">Shorter stack traces</span>
            </button>

            <div class="divider"></div>
            <div class="label">Actions</div>

            <button wire:click="clearCacheAll">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-database" /></svg>
                Clear cache for all files
            </button>

            <button x-data="{ copied: false }" x-clipboard="window.location.href" x-on:click.stop="copied = true; setTimeout(() => copied = false, 1000)">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-share" /></svg>
                <span x-show="!copied">Share this page</span>
                <span x-show="copied" class="text-emerald-600">Link copied!</span>
            </button>

            <div class="divider"></div>

            <a href="https://www.github.com/opcodesio/log-viewer" target="_blank">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-question" /></svg>
                Help
            </a>
        </div>
    </div>
</div>
