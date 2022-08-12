<label for="query" class="sr-only">Search</label>
<div class="relative search">
    <input wire:model.lazy="query" name="query" id="query" type="text" placeholder="Search..." />
    @if(!empty($query))
    <div class="clear-search">
        <button wire:click="clearQuery">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-cross" /></svg>
        </button>
    </div>
    @endif
</div>
