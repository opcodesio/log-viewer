<div wire:ignore.self class="search" x-bind:class="{'has-error': $store.search.error}"
     x-init='$store.search.update(@json($query), @json($queryError), @json(route('blv.search-more')), @json($hasMoreResults), @json($percentScanned))'
>
    <div class="prefix-icon">
        <label for="query" class="sr-only">Search</label>
        <svg xmlns="http://www.w3.org/2000/svg" wire:loading.class="hidden" wire:target="query" class="h-4 w-4" x-bind:class="{'hidden': $store.search.searching}" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-search" /></svg>
        <svg xmlns="http://www.w3.org/2000/svg" wire:loading.class.remove="hidden" wire:target="query" class="spin w-5 h-5 -mr-1" x-bind:class="{'hidden': !$store.search.searching}" viewBox="0 0 24 24" fill="currentColor"><use href="#icon-spinner" /></svg>
    </div>
    <div class="relative flex-1 m-1">
        <input wire:model.lazy="query" name="query" id="query" type="text" />
        <div x-show="$store.search.query !== ''" class="clear-search">
            <button wire:click="clearQuery">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-cross" /></svg>
            </button>
        </div>
    </div>
    <div class="submit-search">
        @if($hasMoreResults)
        <button disabled="disabled">
            <span>Searching {{ isset($file) ? $file->name : 'all files' }}...</span>
        </button>
        @else
        <button wire:click="submitSearch">
            <span>Search {{ isset($file) ? 'in "' . \Illuminate\Support\Str::limit($file->name, 30).'"' : 'all files' }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-arrow-right" /></svg>
        </button>
        @endif
    </div>
</div>
<div class="relative h-0 w-full overflow-visible">
    <div class="search-progress-bar" x-show="$store.search.searching" x-bind:style="{ width: $store.search.percentScanned + '%' }"></div>
</div>
<script wire:key="{{ 'search-key-'.md5($query) }}" x-init='$store.search.update(@json($query), @json($queryError), @json(route('blv.search-more')), @json($hasMoreResults), @json($percentScanned))'>
</script>
<p class="mt-1 text-red-600 text-xs" x-show="$store.search.error" x-html="$store.search.error"></p>
