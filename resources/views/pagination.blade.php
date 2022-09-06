<nav class="pagination" wire:key="pagination-next-{{ $paginator->currentPage() }}">
    <div class="previous">
        @if(!$paginator->onFirstPage())
        <button wire:click="previousPage" wire:loading.attr="disabled" rel="prev">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><use href="#icon-arrow-left" /></svg>
        </button>
        @endif
    </div>
    <div class="pages">
        @php
            $links = $paginator->toArray()['links'];
            // To get rid of the "previous" and "next" links
            array_shift($links);
            array_pop($links);
        @endphp
        @foreach($links as $link)
            @if($link['active'])
                <button class="border-emerald-500 text-emerald-600 dark:border-emerald-600 dark:text-emerald-500" aria-current="page">
                    {{ number_format($link['label']) }}
                </button>
            @elseif($link['label'] === '...')
                <span>{{ $link['label'] }}</span>
            @else
                <button wire:click="gotoPage({{ intval($link['label']) }})" class="border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 hover:border-gray-300 dark:hover:text-gray-300 dark:hover:border-gray-400">
                    {{ number_format($link['label']) }}
                </button>
            @endif
        @endforeach
    </div>
    <div class="next">
        @if($paginator->hasMorePages())
        <button wire:click="nextPage" wire:loading.attr="disabled" rel="next">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><use href="#icon-arrow-right" /></svg>
        </button>
        @endif
    </div>
</nav>
