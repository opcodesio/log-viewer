@foreach($levels as $levelCount)
    @continue($levelCount->count === 0)
    <span class="badge {{ $levelCount->level->getClass() }} @if($levelCount->selected) active @endif"
          wire:click="toggleLevel('{{ $levelCount->level->value }}')"
    >
        <x-log-viewer::checkmark class="checkmark mr-2.5" :checked="$levelCount->selected" />
        <span class="opacity-90">{{ $levelCount->level->getName() }}:</span>
        <span class="font-semibold ml-2">{{ number_format($levelCount->count) }}</span>
    </span>
@endforeach
