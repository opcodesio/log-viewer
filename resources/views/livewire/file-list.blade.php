<div class="relative overflow-hidden" wire:init="loadFiles">
    <div class="absolute top-0 h-6 w-full bg-gradient-to-b from-gray-100 to-transparent"></div>
    <div class="h-full overflow-y-scroll py-6">
        @foreach($files as $logFile)
            <div wire:click="selectFile('{{ $logFile->name }}')"
                 class="mb-2 text-gray-800 rounded-md bg-white overflow-hidden transition duration-100 border-2 border-transparent hover:border-emerald-600 cursor-pointer @if($file === $logFile->name) border-emerald-500 @endif">
                <div
                    class="flex justify-between items-center px-4 py-2">
                    <p class="text-sm mr-3 whitespace-nowrap">{{ $logFile->name }}</p>
                    <span class="text-xs text-gray-500 whitespace-nowrap">{{ $logFile->sizeFormatted() }}</span>
                </div>
            </div>
        @endforeach
    </div>
    <div class="absolute bottom-0 h-8 w-full bg-gradient-to-t from-gray-100 to-transparent"></div>
</div>
