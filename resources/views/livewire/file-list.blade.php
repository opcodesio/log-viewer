<div class="relative overflow-hidden" x-cloak wire:init="loadFiles">
    <div class="absolute z-10 top-0 h-6 w-full bg-gradient-to-b from-gray-100 dark:from-gray-900 to-transparent"></div>
    <div class="file-list">
        @if(!$shouldLoadFiles)
            <div class="w-full h-full flex flex-col items-center justify-center text-gray-500 text-sm text-center">
                <div class="loader opacity-30">Loading...</div>
                <p>Scanning for files & indexing log entries.</p>
                <p class="mt-5">This might take a bit longer on the first run.</p>
            </div>
        @endif

        @foreach($files as $logFile)
            @include('log-viewer::partials.file-list-item', ['logFile' => $logFile])
        @endforeach
    </div>
    <div class="absolute z-10 bottom-0 h-8 w-full bg-gradient-to-t from-gray-100 dark:from-gray-900 to-transparent"></div>
</div>
