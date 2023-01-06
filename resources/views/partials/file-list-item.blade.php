<div class="file-item-container flex"
    x-bind:class="[selectedFileIdentifier && selectedFileIdentifier === '{{ $logFile->identifier }}' ? 'active' : '']"
    wire:key="log-file-{{$logFile->identifier}}"
    wire:click="selectFile('{{ $logFile->identifier }}')"
    x-on:click="selectFile('{{ $logFile->identifier }}')"
    x-data="dropdown"
    x-on:keydown.escape.prevent.stop="close($refs.button)"
    x-on:focusin.window="! $refs.panel.contains($event.target) && close()"
    x-id="['dropdown-button']"
>
    <div class="file-item grow">
        @can('deleteLogFile', $logFile)
        <div class="my-auto mr-2" x-show="$store.fileViewer.checkBoxesVisibility">
            <input type="checkbox"
                x-init="$watch(`$store.fileViewer.isChecked('{{$logFile->identifier}}')`, (value) => {
                    $el.checked = value;
                })"
                x-on:click.stop="$store.fileViewer.checkBoxToggle('{{$logFile->identifier}}')"
                value="{{$logFile->identifier}}" />
        </div>
        @endcan
        <p class="file-name">{{ $logFile->name }}</p>
        <span class="file-size">{{ $logFile->sizeFormatted() }}</span>
        <button type="button" class="file-dropdown-toggle"
                x-ref="button" x-on:click.stop="toggle()" :aria-expanded="open" :aria-controls="$id('dropdown-button')"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-more" /></svg>
        </button>
    </div>

    <div
        x-ref="panel"
        x-show="open"
        x-bind="transitions"
        x-on:click.outside="close($refs.button)"
        :id="$id('dropdown-button')"
        class="dropdown w-48"
        :class="direction"
    >
        <div class="py-2">
            <button wire:click="clearCache('{{ $logFile->identifier }}')" x-on:click.stop="cacheRecentlyCleared = false;" x-data="{ cacheRecentlyCleared: @json($cacheRecentlyCleared) }" x-init="setTimeout(() => cacheRecentlyCleared = false, 2000)">
                <svg xmlns="http://www.w3.org/2000/svg" wire:loading.class="hidden" fill="currentColor"><use href="#icon-database" /></svg>
                <svg xmlns="http://www.w3.org/2000/svg" wire:loading.class.remove="hidden" class="hidden spin" fill="currentColor"><use href="#icon-spinner" /></svg>
                <span x-show="!cacheRecentlyCleared" wire:loading.class="hidden">Clear index</span>
                <span x-show="!cacheRecentlyCleared" wire:loading wire:target="clearCache('{{ $logFile->identifier }}')">Clearing...</span>
                <span x-show="cacheRecentlyCleared" class="text-emerald-500">Index cleared</span>
            </button>

            @can('downloadLogFile', $logFile)
            <a href="{{ $logFile->downloadUrl() }}" x-on:click.stop="">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-download" /></svg>
                Download
            </a>
            @endcan

            @can('deleteLogFile', $logFile)
            <div class="divider"></div>
            <button x-on:click.stop="if (confirm('Are you sure you want to delete the log file \'{{ $logFile->name }}\'? THIS ACTION CANNOT BE UNDONE.')) { $wire.call('deleteFile', '{{ $logFile->identifier }}') }">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-trashcan" /></svg>
                Delete
            </button>
            <button x-on:click.stop="$store.fileViewer.showCheckBoxes(); $store.fileViewer.checkBoxToggle('{{$logFile->identifier}}'); close($refs.button)">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-trashcan" /></svg>
                Delete Multiple
            </button>
            @endcan
        </div>
    </div>
</div>
