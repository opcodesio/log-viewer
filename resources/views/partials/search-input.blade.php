<label for="query" class="sr-only">Search</label>
<div class="relative">
    <input wire:model.lazy="query" name="query" id="query" type="text" class="border rounded-md pl-3 pr-10 py-2 text-sm w-full focus:outline-emerald-500 focus:border-transparent border-gray-300" placeholder="Search..." />
    @if(!empty($query))
    <div class="absolute top-0 right-0 p-1.5">
        <button class="text-gray-500 hover:text-gray-600 p-1" wire:click="clearQuery">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
        </button>
    </div>
    @endif
</div>
