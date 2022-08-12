<nav class="pagination" wire:key="pagination-next-{{ $paginator->currentPage() }}">
    <div class="previous">
        @if(!$paginator->onFirstPage())
        <button wire:click="previousPage" wire:loading.attr="disabled" rel="prev" class="border-t-2 border-transparent pt-4 pr-1 inline-flex items-center text-sm font-medium text-emerald-600 hover:text-emerald-700 hover:border-gray-300">
            <svg class="mx-3 h-5 w-5 text-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><use href="#icon-arrow-left" /></svg>
        </button>
        @endif
    </div>
    <div class="pages">
        @php
            $links = $paginator->linkCollection()->toArray();
            // To get rid of the "previous" and "next" links
            array_shift($links);
            array_pop($links);
        @endphp
        @foreach($links as $link)
            @if($link['active'])
                <button class="border-emerald-500 text-emerald-600" aria-current="page">
                    {{ number_format($link['label']) }}
                </button>
            @elseif($link['label'] === '...')
                <span>{{ $link['label'] }}</span>
            @else
                <button wire:click="gotoPage({{ intval($link['label']) }})" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    {{ number_format($link['label']) }}
                </button>
            @endif
        @endforeach
    </div>
    <div class="next">
        @if($paginator->hasMorePages())
        <button wire:click="nextPage" wire:loading.attr="disabled" rel="next" class="border-t-2 border-transparent pt-4 pl-1 inline-flex items-center text-sm font-medium text-emerald-600 hover:text-emerald-700 hover:border-gray-300">
            <svg class="mx-3 h-5 w-5 text-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><use href="#icon-arrow-right" /></svg>
        </button>
        @endif
    </div>
</nav>
