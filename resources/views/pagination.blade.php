<nav class=" border-gray-200 mt-2 px-4 w-full flex items-center justify-center sm:px-0">
    <div class="-mt-px w-0 flex-1 flex justify-end" wire:key="pagination-previous-{{ $paginator->currentPage() }}">
        @if(!$paginator->onFirstPage())
        <button wire:click="previousPage" wire:loading.attr="disabled" rel="prev" class="border-t-2 border-transparent pt-4 pr-1 inline-flex items-center text-sm font-medium text-emerald-600 hover:text-emerald-700 hover:border-gray-300">
            <!-- Heroicon name: solid/arrow-narrow-left -->
            <svg class="mx-3 h-5 w-5 text-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M7.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
        </button>
        @endif
    </div>
    <div class="hidden md:-mt-px md:flex" wire:key="pagination-page-{{ $paginator->currentPage() }}">
        @php
            $links = $paginator->linkCollection()->toArray();
            // To get rid of the "previous" and "next" links
            array_shift($links);
            array_pop($links);
        @endphp
        @foreach($links as $link)
            @if($link['active'])
                <button class="border-emerald-500 text-emerald-600 border-t-2 pt-4 px-4 inline-flex items-center text-sm font-medium" aria-current="page">
                    {{ number_format($link['label']) }}
                </button>
            @elseif($link['label'] === '...')
                <span class="border-transparent text-gray-500 border-t-2 pt-4 px-4 inline-flex items-center text-sm font-medium">{{ $link['label'] }}</span>
            @else
                <button wire:click="gotoPage({{ intval($link['label']) }})" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 border-t-2 pt-4 px-4 inline-flex items-center text-sm font-medium">
                    {{ number_format($link['label']) }}
                </button>
            @endif
        @endforeach
    </div>
    <div class="-mt-px w-0 flex-1 flex justify-start" wire:key="pagination-next-{{ $paginator->currentPage() }}">
        @if($paginator->hasMorePages())
        <button wire:click="nextPage" wire:loading.attr="disabled" rel="next" class="border-t-2 border-transparent pt-4 pl-1 inline-flex items-center text-sm font-medium text-emerald-600 hover:text-emerald-700 hover:border-gray-300">
            <!-- Heroicon name: solid/arrow-narrow-right -->
            <svg class="mx-3 h-5 w-5 text-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
        @endif
    </div>
</nav>
