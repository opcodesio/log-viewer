<label for="query" class="sr-only">Search</label>
<div class="relative search @if(!empty($queryError)) has-error @endif">
    <div class="prefix-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-search" /></svg>
    </div>
    <input wire:model.debounce.750ms="query" name="query" id="query" type="text" />
    @if(!empty($query))
    <div class="clear-search">
        <button wire:click="clearQuery">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-cross" /></svg>
        </button>
    </div>
    @endif
</div>
@if(!empty($queryError))
<p class="mt-1 text-red-600 text-xs">{{ $queryError }}</p>
@endif
@if(isset($file) && !empty($query))
<p class="mt-1 text-gray-500 text-xs">
    Searching in {{ $file->name }}.
    <a href="" x-on:click.prevent="selectFile(null)" class="text-sky-700 dark:text-sky-300">Click here to search all files</a>
</p>
@endif
