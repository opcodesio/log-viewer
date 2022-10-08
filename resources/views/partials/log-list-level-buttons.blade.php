@php
    $levelsFound = array_filter($levels ?? [], fn ($level) => $level->count > 0);
    $levelsSelected = array_values(array_filter($levelsFound, fn ($level) => $level->selected));
    $totalLogsFound = array_sum(array_map(fn ($level) => $level->count, $levelsFound));
@endphp
<div class="flex items-center">
    <div class="mr-5 relative log-levels-selector"
        x-data="dropdown"
        x-on:keydown.escape.prevent.stop="close($refs.button)"
        x-on:focusin.window="! $refs.panel.contains($event.target) && close()"
        x-id="['dropdown-button']"
    >
        <button type="button" class="dropdown-toggle badge none @if(count($levelsSelected)) active @endif"
                x-ref="button" x-on:click.stop="toggle()" :aria-expanded="open" :aria-controls="$id('dropdown-button')"
        >
            @if(count($levelsSelected) > 2)
            <span class="opacity-90 mr-1">{{ number_format($logs->total()) }}@if($hasMoreResults)+@endif entries in</span>
            <strong class="font-semibold">{{ $levelsSelected[0]->level->getName() }} + {{ count($levelsSelected) - 1 }} more</strong>
            @elseif(count($levelsSelected) > 0)
            <span class="opacity-90 mr-1">{{ number_format($logs->total()) }}@if($hasMoreResults)+@endif entries in</span>
            <strong class="font-semibold">{{ implode(', ', array_map(fn ($levelCount) => $levelCount->level->getName(), $levelsSelected)) }}</strong>
            @elseif(count($levelsFound))
            <span class="opacity-90">{{ number_format($totalLogsFound) }}@if($hasMoreResults)+@endif entries found. None selected</span>
            @else
            <span class="opacity-90">No entries found</span>
            @endif
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-chevron-down" /></svg>
        </button>

        <div
            x-ref="panel"
            x-show="open"
            x-bind="transitions"
            x-on:click.outside="close($refs.button)"
            :id="$id('dropdown-button')"
            class="dropdown left min-w-[200px]"
            :class="direction"
        >
            <div class="py-2">
                <div class="label flex justify-between">
                    Severity
                    @if(count($levelsFound))
                        @if(count($levelsSelected) === count($levelsFound))
                        <span wire:click.stop="deselectAllLevels" class="cursor-pointer text-sky-700 dark:text-sky-500 font-normal hover:text-sky-800 dark:hover:text-sky-400">Deselect all</span>
                        @else
                        <span wire:click.stop="selectAllLevels" class="cursor-pointer text-sky-700 dark:text-sky-500 font-normal hover:text-sky-800 dark:hover:text-sky-400">Select all</span>
                        @endif
                    @endif
                </div>
                @forelse($levelsFound as $levelCount)
                <button wire:click="toggleLevel('{{ $levelCount->level->value }}')">
                    <x-log-viewer::checkmark class="checkmark mr-2.5" :checked="$levelCount->selected" />
                    <span class="flex-1 inline-flex justify-between">
                        <span class="log-level {{ $levelCount->level->getClass() }}">{{ $levelCount->level->getName() }}</span>
                        <span class="log-count">{{ number_format($levelCount->count) }}</span>
                    </span>
                </button>
                @empty
                <div class="no-results">There are no severity filters to display because no entries have been found.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
