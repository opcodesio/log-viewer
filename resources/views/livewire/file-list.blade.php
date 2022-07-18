<div class="relative overflow-hidden" wire:init="loadFiles">
    <div class="absolute top-0 h-6 w-full bg-gradient-to-b from-gray-100 to-transparent"></div>
    <div class="h-full overflow-y-scroll py-6">
        @foreach($files as $file)
            <div class="mb-2 px-3 py-2 text-gray-800 rounded-md bg-white border-2 border-transparent cursor-pointer hover:border-emerald-600">
                <div class="flex justify-between items-center">
                    <p class="text-sm mr-3">{{ $file->name }}</p>
                    <span class="text-xs text-gray-500">{{ $file->sizeFormatted() }}</span>
                </div>
            </div>
        @endforeach
    </div>
    <div class="absolute bottom-0 h-8 w-full bg-gradient-to-t from-gray-100 to-transparent"></div>
</div>
