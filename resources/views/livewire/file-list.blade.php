<div class="relative overflow-hidden" x-cloak>
    <div class="absolute z-10 top-0 h-6 w-full bg-gradient-to-b from-gray-100 dark:from-gray-900 to-transparent"></div>
    <div class="file-list">
        @foreach($files as $logFile)
            <div class="file-item-container"
                x-bind:class="[selectedFileName && selectedFileName === '{{ $logFile->name }}' ? 'active' : '']"
                wire:key="log-file-{{$logFile->name}}"
                wire:click="selectFile('{{ $logFile->name }}')"
                x-on:click="selectFile('{{ $logFile->name }}')"
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
                    x-transition.origin.top.right
                    x-on:click.outside="close($refs.button)"
                    :id="$id('dropdown-button')"
                    style="display: none;"
                    class="dropdown w-48"
                >
                    <div class="py-2">
                        <button wire:click="clearCache('{{ $logFile->name }}')" x-on:click.stop="close($refs.button)">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-database" /></svg>
                            Clear cache
                        </button>

                        <button wire:click.stop="download('{{ $logFile->name }}')">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-download" /></svg>
                            Download
                        </button>

                        @can('deleteLogFile', $logFile)
                        <div class="divider"></div>
                        <button x-on:click.stop="if (confirm('Are you sure you want to delete the log file \'{{ $logFile->name }}\'')) { $wire.call('deleteFile', '{{ $logFile->name }}') }">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-trashcan" /></svg>
                            Delete
                        </button>
                        @endcan
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="absolute z-10 bottom-0 h-8 w-full bg-gradient-to-t from-gray-100 dark:from-gray-900 to-transparent"></div>
</div>
