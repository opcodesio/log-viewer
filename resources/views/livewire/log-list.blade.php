@php
    /** @var \Arukompas\BetterLogViewer\LogFile $file */
@endphp
<div class="h-full w-full py-5">
    @empty($selectedFileName)
        <div class="flex h-full items-center justify-center">
            Please select a file...
        </div>
    @else
        <div class="flex flex-col h-full w-full mx-3 mb-4">
            <div class="px-4 mb-4 flex items-center">
                <div class="flex-1 mr-6">
                    @foreach($levels as $levelCount)
                        @continue($levelCount->count === 0)
                        <span class="badge {{ $levelCount->level->getClass() }} @if($levelCount->selected) active @endif"
                              wire:click="toggleLevel('{{ $levelCount->level->value }}')"
                        >
                            <x-better-log-viewer::checkmark class="checkmark mr-2.5" :checked="$levelCount->selected" />
                            <span class="opacity-90">{{ $levelCount->level->name }}:</span>
                            <span class="font-semibold ml-2">{{ number_format($levelCount->count) }}</span>
                        </span>
                    @endforeach
                </div>
                <div class="flex-1">
                    <label for="query" class="sr-only">Search</label>
                    <div class="relative">
                        <input name="query" id="query" type="text"
                               class="border rounded-md pl-3 pr-10 py-2 text-sm w-full focus:outline-emerald-500 focus:border-transparent border-gray-300" placeholder="Search..."
                               wire:model.lazy="query"
                        />
                        <div class="absolute top-0 right-0 p-1.5">
                            @if(!empty($query))
                            <button class="text-gray-500 hover:text-gray-600 p-1" wire:click="clearQuery">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative overflow-hidden text-sm"
x-data="{
    stacksOpen: [],
    stacksInView: [],
    stackTops: {},
    containerTop: 0,
    isOpen(index) { return this.stacksOpen.includes(index); },
    toggle(index) {
        if (this.isOpen(index)) {
            this.stacksOpen = this.stacksOpen.filter(idx => idx !== index)
        } else {
            this.stacksOpen.push(index)
        }
        this.onScroll();
    },
    shouldBeSticky(index) {
        return this.isOpen(index) && this.stacksInView.includes(index);
    },
    stickTopPosition(index) {
        var aboveFold = this.pixelsAboveFold(index);
        if (aboveFold < 0) { return Math.max(0, 36 + aboveFold) + 'px'; }
        return '36px';
    },
    pixelsAboveFold(index) {
        var tbody = document.getElementById('tbody-'+index);
        if (!tbody) return false;
        var row = tbody.getClientRects()[0];
        return (row.top + row.height - 73) - this.containerTop;
    },
    isInViewport(index) {
        var pixels = this.pixelsAboveFold(index);
        return pixels > -36;
    },
    onScroll(event) {
        var vm = this;
        this.stacksOpen.forEach(function (index) {
            if (vm.isInViewport(index)) {
                if (!vm.stacksInView.includes(index)) { vm.stacksInView.push(index); }
                vm.stackTops[index] = vm.stickTopPosition(index);
            } else {
                vm.stacksInView = vm.stacksInView.filter(idx => idx !== index);
                delete vm.stackTops[index];
            }
        })
    },
    reset() {
        this.stacksOpen = [];
        this.stacksInView = [];
        this.stackTops = {};
        const container = document.getElementById('log-item-container');
        this.containerTop = container.getBoundingClientRect().top;
        container.scrollTo(0, 0);
    }
}"
x-init="reset()"
            >

                <div id="log-item-container" class="log-item-container h-full overflow-y-scroll px-4" x-on:scroll="onScroll">
                    <div class="inline-block min-w-full max-w-full align-middle">
                        <div class="">
                            <table wire:key="{{ \Illuminate\Support\Str::random(16) }}"
                                   class="table-fixed min-w-full max-w-full border-separate"
                                   style="border-spacing: 0"
                                   x-init="reset()"
                            >
                                <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col"
                                        class="w-[60px] sticky top-0 z-10 bg-gray-100 py-2 pl-4 pr-2 text-left text-sm font-semibold text-gray-500 sm:pl-6 lg:pl-8">
                                        <span class="sr-only">Level icon</span>
                                    </th>
                                    <th scope="col"
                                        class="w-[90px] sticky top-0 z-10 hidden bg-gray-100 px-2 py-2 text-left text-sm font-semibold text-gray-500 lg:table-cell">
                                        Level
                                    </th>
                                    <th scope="col"
                                        class="w-[180px] sticky top-0 z-10 bg-gray-100 py-2 px-2 text-left text-sm font-semibold text-gray-500 sm:table-cell">
                                        Time
                                    </th>
                                    <th scope="col"
                                        class="w-[110px] sticky top-0 z-10 hidden bg-gray-100 px-2 py-2 text-left text-sm font-semibold text-gray-500 lg:table-cell">
                                        Env
                                    </th>
                                    <th scope="col"
                                        class="sticky top-0 z-10 bg-gray-100 px-2 py-2 text-left text-sm font-semibold text-gray-500 rounded-tr-md">
                                        Description
                                    </th>
                                    <th scope="col"
                                        class="sticky top-0 z-10 bg-gray-100 px-2 py-2 text-left text-sm font-semibold text-gray-500 rounded-tr-md">
                                        <span class="sr-only">Item index</span>
                                    </th>
                                </tr>
                                </thead>

                                @forelse($logs as $index => $log)
                                <tbody id="tbody-{{$index}}" data-index="{{ $index }}" class="bg-white relative">
                                    <tr class="log-item-2 {{ $log->level->getClass() }}"
                                        x-on:click="toggle({{$index}})"
                                        x-bind:class="[isOpen({{$index}}) ? 'active' : '', shouldBeSticky({{$index}}) ? 'sticky z-2' : '']"
                                        x-bind:style="{ top: stackTops[{{$index}}] || 0 }"
                                    >
                                        <td class="log-level opacity-80 whitespace-nowrap border-t border-gray-200 py-2 pl-4 pr-2 text-sm font-medium text-gray-900 sm:pl-6 lg:pl-8">
                                            @if($log->level->getClass() === 'danger')
                                                <x-better-log-viewer::icon-danger />
                                            @elseif($log->level->getClass() === 'warning')
                                                <x-better-log-viewer::icon-warning />
                                            @else
                                                <x-better-log-viewer::icon-info />
                                            @endif
                                        </td>
                                        <td class="log-level truncate border-t border-gray-200 px-2 py-2 text-sm text-gray-500 hidden lg:table-cell">
                                            {{ ucfirst($log->level->value) }}
                                        </td>
                                        <td class="whitespace-nowrap border-t border-gray-200 py-2 px-2 text-sm text-gray-900">
                                            {{ $log->time->toDateTimeString() }}
                                        </td>
                                        <td class="whitespace-nowrap border-t border-gray-200 px-2 py-2 text-sm text-gray-500 hidden lg:table-cell">
                                            {{ $log->environment }}
                                        </td>
                                        <td class="max-w-[1px] w-full truncate border-t border-gray-200 px-2 py-2 text-sm text-gray-500">
                                            {{ $log->text }}
                                        </td>
                                        <td class="whitespace-nowrap border-t border-gray-200 px-2 py-2 text-sm text-gray-500 text-xs">
                                            <a href="{{ $log->url() }}" class="flex items-center group" x-on:click.stop="">
                                                <span class="group-hover:underline">{{ number_format($log->index) }}</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1 opacity-0 group-hover:opacity-75 transition duration-200" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd" />
                                                </svg>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr x-show="isOpen({{$index}})">
                                        <td colspan="6">
                                            <pre class="log-stack px-4 py-2 sm:px-6 lg:px-8 border-gray-200 text-xs whitespace-pre-wrap break-all">{{ $log->fullText }}</pre>
                                        </td>
                                    </tr>
                                </tbody>
                                @empty
                                <tbody>
                                <tr>
                                    <td colspan="6">
                                        <div class="bg-white rounded p-6 mb-6">
                                            <div class="text-center font-semibold">No results</div>
                                            @if(!empty($query))
                                            <div class="text-center mt-6">
                                                <button class="px-3 py-2 border-2 bg-white text-gray-800 hover:border-emerald-600 rounded-md" wire:click="clearQuery">Clear search query</button>
                                            </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                                @endforelse
                            </table>
                        </div>
                    </div>
                </div>

                <div class="absolute hidden inset-0 top-9 px-4 z-20" wire:loading.class.remove="hidden">
                    <div class="rounded-md bg-white opacity-90 w-full h-full flex items-center justify-center">
                        <div class="loader">Loading...</div>
                    </div>
                </div>
            </div>

            @if($logs->hasPages())
            <div class="px-4 mb-4">
                {{ $logs->links('better-log-viewer::pagination') }}
            </div>
            @endif

            <div class="text-right px-4">
                <p class="text-xs text-gray-400">Memory: <span class="font-semibold">{{ $memoryUsage }}</span>, Duration: <span class="font-semibold">{{ $requestTime }}</span></p>
            </div>
        </div>
    @endempty
</div>
