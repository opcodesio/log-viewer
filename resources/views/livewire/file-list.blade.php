<div id="file-list-container" class="relative h-full overflow-hidden" x-cloak @if(!$shouldLoadFilesImmediately) wire:init="loadFiles" @endif>
    <div class="pointer-events-none absolute z-10 top-0 h-6 w-full bg-gradient-to-b from-gray-100 dark:from-gray-900 to-transparent"></div>
    <div class="file-list" x-ref="list" x-on:scroll="(event) => $store.fileViewer.onScroll(event)">
        @if(!$shouldLoadFilesImmediately)
            <div class="w-full flex flex-col items-center justify-center text-gray-600 dark:text-gray-400 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 opacity-70 spin" fill="currentColor"><use href="#icon-spinner" /></svg>
                <p class="mt-5">Scanning <strong>{{ bytes_formatted($totalFileSize) }}</strong> worth of log files.</p>
                <p class="mt-5 text-sm">We are indexing these files to improve performance later on.</p>
                <p class="mt-5 text-sm">This should take ~ {{ $estimatedTimeToScan }}.</p>
            </div>
        @endif

        @if($shouldLoadFilesImmediately)
        <div class="text-sm text-gray-600 dark:text-gray-400 mb-4 ml-1">
            <label for="file-sort-direction" class="sr-only">Sort direction</label>
            <select id="file-sort-direction" wire:model="direction" class="bg-gray-100 dark:bg-gray-900 px-2 font-normal mr-3 outline-none rounded focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-600">
                <option value="desc">Newest first</option>
                <option value="asc">Oldest first</option>
            </select>
        </div>
        @endif
@php /** @var \Opcodes\LogViewer\LogFolder $folder */ @endphp
@foreach($filesGrouped as $folder)
    <div x-data="{ folder: '{{ $folder->identifier }}' }" :id="'folder-'+folder"
         class="relative @if(!$folder->isRoot()) folder-container @endif"
    >
        @if(!$folder->isRoot())
        <div class="folder-item-container"
             x-on:click="$store.fileViewer.toggle(folder)"
             x-bind:class="[$store.fileViewer.isOpen(folder) ? 'active' : '', $store.fileViewer.shouldBeSticky(folder) ? 'sticky z-10' : '']"
             x-bind:style="{ top: $store.fileViewer.isOpen(folder) ? ($store.fileViewer.folderTops[folder] || 0) : 0 }"
        >
            <div class="file-item">
                @include('log-viewer::partials.folder-icon')
                <div class="file-name">{{ $folder->cleanPath() }}</div>
            </div>
        </div>
        @endif

        <div class="folder-files @if(!$folder->isRoot()) pl-3 ml-1 border-l border-gray-200 dark:border-gray-800 @endif" @if(!$folder->isRoot()) x-show="$store.fileViewer.isOpen(folder)" @endif>
        @foreach($folder->files as $logFile)
            @include('log-viewer::partials.file-list-item', ['logFile' => $logFile])
        @endforeach
        </div>
    </div>
@endforeach
    </div>
    <div class="pointer-events-none absolute z-10 bottom-0 h-8 w-full bg-gradient-to-t from-gray-100 dark:from-gray-900 to-transparent"></div>
</div>
