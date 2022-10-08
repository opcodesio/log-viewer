<div wire:ignore class="search" x-bind:class="{'has-error': $store.search.error}"
     x-init='$store.search.update(@json($query), @json($queryError), @json(route('blv.search-more')), @json($hasMoreResults), @json($percentScanned))'
>
    <div class="prefix-icon">
        <label for="query" class="sr-only">Search</label>
        <svg xmlns="http://www.w3.org/2000/svg" wire:loading.class="hidden" wire:target="query" class="h-4 w-4" x-bind:class="{'hidden': $store.search.searching}" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-search" /></svg>
        <svg xmlns="http://www.w3.org/2000/svg" wire:loading.class.remove="hidden" wire:target="query" class="spin w-5 h-5 -mr-1" x-bind:class="{'hidden': !$store.search.searching}" viewBox="0 0 24 24" fill="currentColor"><use href="#icon-spinner" /></svg>
    </div>
    <input wire:model.debounce.750ms="query" name="query" id="query" type="text" />
    <div x-show="$store.search.query !== ''" class="clear-search">
        <button wire:click="clearQuery">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-cross" /></svg>
        </button>
    </div>
</div>
<div class="relative h-0 w-full overflow-visible">
    <div class="search-progress-bar" x-show="$store.search.searching" x-bind:style="{ width: $store.search.percentScanned + '%' }"></div>
</div>
<script wire:key="{{ 'search-key-'.md5($query) }}" x-init='$store.search.update(@json($query), @json($queryError), @json(route('blv.search-more')), @json($hasMoreResults), @json($percentScanned))'>
</script>
<p class="mt-1 text-red-600 text-xs" x-show="$store.search.error" x-html="$store.search.error"></p>
@if(isset($file) && !empty($query))
<p class="mt-1 text-gray-500 text-xs">
    Searching in {{ $file->name }}.
    <a href="" x-on:click.prevent="selectFile(null)" class="text-sky-700 dark:text-sky-300">Click here to search all files</a>
</p>
@endif
