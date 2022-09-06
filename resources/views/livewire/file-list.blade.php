<div class="relative h-full overflow-hidden" x-cloak>
    <div class="pointer-events-none absolute z-10 top-0 h-6 w-full bg-gradient-to-b from-gray-100 dark:from-gray-900 to-transparent"></div>
    <div class="file-list" x-ref="list">
        @foreach($files as $subFolder => $logFiles)
            @if($subFolder !== '_root')
                <div class="text-gray-400">{{ $subFolder }}</div>
            @endif
            @foreach($logFiles as $logFile)
                <div class="file-item-container"
                     x-bind:class="[selectedFileIdentifier && selectedFileIdentifier === '{{ $logFile->identifier }}' ? 'active' : '']"
                     wire:key="log-file-{{$logFile->identifier}}"
                     wire:click="selectFile('{{ $logFile->identifier }}')"
                     x-on:click="selectFile('{{ $logFile->identifier }}')"
                     x-data="{
                    open: false,
                    direction: 'down',
                    toggle() {
                        if (this.open) { return this.close() }
                        this.$refs.button.focus()
                        this.open = true
                        const p = this.$refs.list.getBoundingClientRect()
                        this.direction = this.$refs.button.getBoundingClientRect().bottom - p.top + 140 > p.height ? 'up' : 'down';
                    },
                    close(focusAfter) {
                        if (! this.open) { return }
                        this.open = false
                        focusAfter?.focus()
                    }
                }"
                     x-on:keydown.escape.prevent.stop="close($refs.button)"
                     x-on:focusin.window="! $refs.panel.contains($event.target) && close()"
                     x-id="['dropdown-button']"
                >
                    <div class="file-item">
                        <p class="file-name">
                            <span class="text-gray-400 dark:text-gray-500">{{ str_replace(DIRECTORY_SEPARATOR, ' '.DIRECTORY_SEPARATOR.' ', $logFile->subFolder) }}</span> {{ $logFile->name }}
                        </p>
                        <span class="file-size">{{ $logFile->sizeFormatted() }}</span>
                        <button type="button" class="file-dropdown-toggle"
                                x-ref="button" x-on:click.stop="toggle()" :aria-expanded="open" :aria-controls="$id('dropdown-button')"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <use href="#icon-more" />
                            </svg>
                        </button>
                    </div>

                    <div
                        x-ref="panel"
                        x-show="open"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-90"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-90"
                        x-on:click.outside="close($refs.button)"
                        :id="$id('dropdown-button')"
                        class="dropdown w-48"
                        :class="direction"
                    >
                        <div class="py-2">
                            <button wire:click="clearCache('{{ $logFile->identifier }}')" x-on:click.stop="close($refs.button)">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <use href="#icon-database" />
                                </svg>
                                Clear cache
                            </button>

                            @can('downloadLogFile', $logFile)
                                <a href="{{ $logFile->downloadUrl() }}" x-on:click.stop="">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <use href="#icon-download" />
                                    </svg>
                                    Download
                                </a>
                            @endcan

                            @can('deleteLogFile', $logFile)
                                <div class="divider"></div>
                                <button x-on:click.stop="if (confirm('Are you sure you want to delete the log file \'{{ $logFile->name }}\'')) { $wire.call('deleteFile', '{{ $logFile->identifier }}') }">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <use href="#icon-trashcan" />
                                    </svg>
                                    Delete
                                </button>
                            @endcan
                        </div>
                    </div>
                </div>
            @endforeach
        @endforeach
    </div>
    <div class="pointer-events-none absolute z-10 bottom-0 h-8 w-full bg-gradient-to-t from-gray-100 dark:from-gray-900 to-transparent"></div>
</div>
