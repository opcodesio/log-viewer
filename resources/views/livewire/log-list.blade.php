<div class="h-full w-full py-5 log-list" @if($refreshAutomatically) wire:poll @endif
    x-cloak
    x-data
    x-on:file-selected.window="$wire.call('selectFile', $event.detail)"
>
@empty($selectedFileName)
    <div class="flex h-full items-center justify-center">
        Please select a file...
    </div>
@else
    <div class="flex flex-col h-full w-full mx-3 mb-4">
        <div class="px-4 mb-4 flex items-start">
            <div class="flex-1 flex items-center mr-6">
                <div>@include('log-viewer::partials.log-list-level-buttons')</div>
            </div>
            <div class="flex-1 flex items-center">
                <div class="flex-1">@include('log-viewer::partials.search-input')</div>
                <div class="ml-5">@include('log-viewer::partials.log-list-share-page-button')</div>
                <div class="ml-2">@include('log-viewer::partials.site-settings-dropdown')</div>
            </div>
        </div>

        <div class="relative overflow-hidden text-sm" x-data x-init="$store.logViewer.reset(); $nextTick(() => { if ({{ $expandAutomatically ? 'true' : 'false' }}) { $store.logViewer.stacksOpen.push(0) } })">

            <div id="log-item-container" class="log-item-container h-full overflow-y-scroll px-4" x-on:scroll="(event) => $store.logViewer.onScroll(event)">
                <div class="inline-block min-w-full max-w-full align-middle">
<table wire:key="{{ \Illuminate\Support\Str::random(16) }}"
       class="table-fixed min-w-full max-w-full border-separate"
       style="border-spacing: 0"
       x-init="$store.logViewer.reset()"
>
<thead class="bg-gray-50">
<tr>
    <th scope="col" class="w-[60px] pl-4 pr-2 sm:pl-6 lg:pl-8"><span class="sr-only">Level icon</span></th>
    <th scope="col" class="w-[90px] hidden lg:table-cell">Level</th>
    <th scope="col" class="w-[180px] hidden sm:table-cell">Time</th>
    <th scope="col" class="w-[110px] hidden lg:table-cell">Env</th>
    <th scope="col" colspan="2">
        <div class="flex justify-between">
            <span>Description</span>
            <div>
                <select wire:model="direction" class="bg-gray-100 px-2 font-normal mr-3 outline-emerald-500">
                    <option value="desc">Newest first</option>
                    <option value="asc">Oldest first</option>
                </select>
                <select wire:model="perPage" class="bg-gray-100 px-2 font-normal outline-emerald-500">
                    <option value="10">10 items per page</option>
                    <option value="25">25 items per page</option>
                    <option value="50">50 items per page</option>
                    <option value="100">100 items per page</option>
                    <option value="250">250 items per page</option>
                    <option value="500">500 items per page</option>
                </select>
            </div>
        </div>
    </th>
</tr>
</thead>

@forelse($logs as $index => $log)
<tbody class="log-group" id="tbody-{{$index}}" data-index="{{ $index }}">
    <tr class="log-item {{ $log->level->getClass() }}"
        x-on:click="$store.logViewer.toggle({{$index}})"
        x-bind:class="[$store.logViewer.isOpen({{$index}}) ? 'active' : '', $store.logViewer.shouldBeSticky({{$index}}) ? 'sticky z-2' : '']"
        x-bind:style="{ top: $store.logViewer.stackTops[{{$index}}] || 0 }"
    >
        <td class="log-level log-level-icon">
@if($log->level->getClass() === 'danger') <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-danger" /></svg>
@elseif($log->level->getClass() === 'warning') <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-warning" /></svg>
@else <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-info" /></svg>@endif
        </td>
        <td class="log-level truncate text-gray-500 hidden lg:table-cell">{{ $log->level->getName() }}</td>
        <td class="whitespace-nowrap text-gray-900">{{ $log->time->toDateTimeString() }}</td>
        <td class="whitespace-nowrap text-gray-500 hidden lg:table-cell">{{ $log->environment }}</td>
        <td class="max-w-[1px] w-full truncate text-gray-500">{{ $log->text }}</td>
        <td class="whitespace-nowrap text-gray-500 text-xs">@include('log-viewer::partials.log-list-link-button')</td>
    </tr>
    <tr x-show="$store.logViewer.isOpen({{$index}})"><td colspan="6"><pre class="log-stack">{{ $log->fullText }}</pre></td></tr>
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

            <div class="absolute hidden inset-0 top-9 px-4 z-20" wire:loading.class.remove="hidden">
                <div class="rounded-md bg-white opacity-90 w-full h-full flex items-center justify-center">
                    <div class="loader">Loading...</div>
                </div>
            </div>
        </div>

        @if($logs->hasPages())
        <div class="px-4">
            {{ $logs->links('log-viewer::pagination') }}
        </div>
        @endif

        <div class="text-right px-4 mt-2">
            <p class="text-xs text-gray-400">Memory: <span class="font-semibold">{{ $memoryUsage }}</span>, Duration: <span class="font-semibold">{{ $requestTime }}</span></p>
        </div>
    </div>
@endempty
</div>
