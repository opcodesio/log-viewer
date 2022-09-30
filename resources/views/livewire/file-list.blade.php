<nav class="flex flex-col h-full py-5" x-data
    x-on:reload-files.window="$wire.call('reloadFiles')"
>
    <div class="mx-3 mb-2">
        <h1 class="font-semibold text-emerald-800 dark:text-emerald-600 text-2xl flex items-center">
            Log Viewer
            <a href="https://www.github.com/opcodesio/log-viewer" target="_blank" class="ml-3 text-gray-400 hover:text-emerald-800 dark:hover:text-emerald-600 p-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><use href="#icon-github" /></svg>
            </a>
        </h1>
        @if($backUrl = config('log-viewer.back_to_system_url'))
            <a href="{{ $backUrl }}" class="inline-flex items-center text-sm text-gray-400 hover:text-emerald-800 dark:hover:text-emerald-600 mt-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1.5" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-arrow-left" /></svg>
                {{ config('log-viewer.back_to_system_label') ?? 'Back to '.config('app.name') }}
            </a>
        @endif

        <div class="flex justify-between mt-4 mr-1">
            <div class="relative">
                <div x-cloak x-show="$store.fileViewer.scanInProgress" class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline spin mr-1" fill="currentColor"><use href="#icon-spinner" /></svg>
                    Indexing logs...
                </div>
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                <label for="file-sort-direction" class="sr-only">Sort direction</label>
                <select id="file-sort-direction" wire:model="direction" class="bg-gray-100 dark:bg-gray-900 px-2 font-normal outline-none rounded focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-600">
                    <option value="desc">Newest first</option>
                    <option value="asc">Oldest first</option>
                </select>
            </div>
        </div>
    </div>

    <div id="file-list-container" class="relative h-full overflow-hidden" x-cloak>
        <div class="pointer-events-none absolute z-10 top-0 h-4 w-full bg-gradient-to-b from-gray-100 dark:from-gray-900 to-transparent"></div>
        <div class="file-list" x-ref="fileList" x-on:scroll="(event) => $store.fileViewer.onScroll(event)">
    @php /** @var \Opcodes\LogViewer\LogFolder $folder */ @endphp
    @foreach($folderCollection as $folder)
        <div x-data="{ folder: '{{ $folder->identifier }}' }" :id="'folder-'+folder"
             class="relative folder-container"
             x-init="$nextTick(() => { if (@json($folder->isRoot() && empty($selectedFileIdentifier))) { $store.fileViewer.foldersOpen.push('{{$folder->identifier}}') } })"
        >
            <div class="folder-item-container"
                 x-on:click="$store.fileViewer.toggle(folder)"
                 x-bind:class="[$store.fileViewer.isOpen(folder) ? 'active' : '', $store.fileViewer.shouldBeSticky(folder) ? 'sticky z-10' : '']"
                 x-bind:style="{ top: $store.fileViewer.isOpen(folder) ? ($store.fileViewer.folderTops[folder] || 0) : 0 }"
                 x-data="dropdown"
                 x-on:keydown.escape.prevent.stop="close($refs.button)"
                 x-on:focusin.window="! $refs.panel.contains($event.target) && close()"
                 x-id="['dropdown-button']"
            >
                <div class="file-item">
                    @include('log-viewer::partials.folder-icon')
                    <div class="file-name @if($folder->isRoot()) text-gray-500 dark:text-gray-400 @endif">{{ $folder->cleanPath() }}</div>
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
                        <button wire:click="clearFolderCache('{{ $folder->identifier }}')" x-on:click.stop="cacheRecentlyCleared = false;" x-data="{ cacheRecentlyCleared: @json($cacheRecentlyCleared) }"
                                x-init="setTimeout(() => cacheRecentlyCleared = false, 2000);"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" wire:loading.class="hidden" wire:target="clearFolderCache" fill="currentColor"><use href="#icon-database" /></svg>
                            <svg xmlns="http://www.w3.org/2000/svg" wire:loading.class.remove="hidden" wire:target="clearFolderCache" class="hidden spin" fill="currentColor"><use href="#icon-spinner" /></svg>
                            <span x-show="!cacheRecentlyCleared" wire:loading.class="hidden" wire:target="clearFolderCache">Rebuild indices</span>
                        </button>

                        @can('downloadLogFolder', $folder)
                        <a href="{{ $folder->downloadUrl() }}" x-on:click.stop="">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-download" /></svg>
                            Download
                        </a>
                        @endcan

                        @can('deleteLogFolder', $folder)
                        <div class="divider"></div>
                        <button x-on:click.stop="if (confirm('Are you sure you want to delete the log folder \'{{ $folder->path }}\'? THIS ACTION CANNOT BE UNDONE.')) { $wire.call('deleteFolder', '{{ $folder->identifier }}') }" wire:loading.attr="disabled">
                            <svg xmlns="http://www.w3.org/2000/svg" wire:loading.class="hidden" wire:target="deleteFolder" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-trashcan" /></svg>
                            <svg xmlns="http://www.w3.org/2000/svg" wire:loading.class.remove="hidden" wire:target="deleteFolder" wire:target="deleteFolder('{{ $folder->identifier }}')" class="hidden spin" fill="currentColor"><use href="#icon-spinner" /></svg>
                            Delete
                        </button>
                        @endcan
                    </div>
                </div>
            </div>

            <div class="folder-files pl-3 ml-1 border-l border-gray-200 dark:border-gray-800" x-show="$store.fileViewer.isOpen(folder)">
            @foreach($folder->files() as $logFile)
                @include('log-viewer::partials.file-list-item', ['logFile' => $logFile])
            @endforeach
            </div>
        </div>
    @endforeach
        </div>
        <div class="pointer-events-none absolute z-10 bottom-0 h-4 w-full bg-gradient-to-t from-gray-100 dark:from-gray-900 to-transparent"></div>
    </div>
</nav>
