<div class="file-item-container"
    x-bind:class="[selectedFileIdentifier && selectedFileIdentifier === '{{ $logFile->identifier }}' ? 'active' : '']"
    wire:key="log-file-{{$logFile->identifier}}"
    wire:click="selectFile('{{ $logFile->identifier }}')"
    x-on:click="selectFile('{{ $logFile->identifier }}')"
    x-data="{
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
    <div class="file-item">
        <p class="file-name"><span class="text-gray-400 dark:text-gray-500">{{ str_replace(DIRECTORY_SEPARATOR, ' '.DIRECTORY_SEPARATOR.' ', $logFile->subFolder) }}</span> {{ $logFile->name }}</p>
        <span class="file-size">{{ $logFile->sizeFormatted() }}</span>
        <button type="button" class="file-dropdown-toggle"
                x-ref="button" x-on:click.stop="toggle()" :aria-expanded="open" :aria-controls="$id('dropdown-button')"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-more" /></svg>
        </button>
    </div>

    <div :id="$id('dropdown-button')" x-ref="panel" x-show="open" x-transition.origin.top.right x-on:click.outside="close($refs.button)" style="display: none;" class="dropdown w-48">
        <div class="py-2">
            <button wire:click="clearCache('{{ $logFile->identifier }}')" x-on:click.stop x-data="{ cacheRecentlyCleared: @json($cacheRecentlyCleared) }" x-init="setTimeout(() => cacheRecentlyCleared = false, 2000)">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-database" /></svg>
                <span x-show="!cacheRecentlyCleared" wire:loading.class="hidden">Rebuild index</span>
                <span x-show="!cacheRecentlyCleared" wire:loading wire:target="clearCache('{{ $logFile->identifier }}')">Rebuilding...</span>
                <span x-show="cacheRecentlyCleared" class="text-emerald-500">Index rebuilt</span>
            </button>

            @can('downloadLogFile', $logFile)
            <a href="{{ $logFile->downloadUrl() }}" x-on:click.stop="">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-download" /></svg>
                Download
            </a>
            @endcan

            @can('deleteLogFile', $logFile)
            <div class="divider"></div>
            <button x-on:click.stop="if (confirm('Are you sure you want to delete the log file \'{{ $logFile->name }}\'')) { $wire.call('deleteFile', '{{ $logFile->identifier }}') }">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-trashcan" /></svg>
                Delete
            </button>
            @endcan
        </div>
    </div>
</div>
