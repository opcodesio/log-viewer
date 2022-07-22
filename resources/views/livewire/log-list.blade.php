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
            <p class="text-gray-600 mb-4">Selected file: <strong>{{ $file->name }}</strong></p>

            <div class="relative overflow-hidden">
                <div class="absolute top-0 h-6 w-full bg-gradient-to-b from-gray-100 to-transparent"></div>

                <div class="log-item-container h-full overflow-y-scroll text-sm py-6 px-4">
                    <div class="rounded-md">
                        @foreach($file->logs()->get(20) as $log)
                        <div class="log-item {{ $log->level->getClass() }}" x-data="{ showStack: false }" x-bind:class="showStack ? 'active' : ''">
                            <div class="cursor-pointer pl-2 pr-4 py-3 flex" x-on:click="showStack = !showStack">
                                <div class="log-level-indicator h-full rounded w-1 mr-2">&nbsp;</div>
                                <div class="flex overflow-hidden">
                                    <div class="whitespace-nowrap">
                                        <span class="log-time">{{ $log->time->toDateTimeString() }}</span>
                                        <span class="mx-1.5">·</span>
                                        <span class="log-level font-semibold">{{ strtoupper($log->level->value) }}</span>
                                        <span class="log-context text-gray-500">@ {{ $log->environment }}</span>
                                        <span class="mx-1.5">·</span>
                                    </div>
                                    <div class="flex-none">
                                        <p class="text-gray-700">{{ $log->text }}</p>
                                    </div>
                                </div>
                            </div>
                            <pre class="log-stack px-3 py-2 border-t border-gray-200 text-xs whitespace-pre-wrap break-all" x-show="showStack">{{ $log->stack }}</pre>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="absolute bottom-0 h-8 w-full bg-gradient-to-t from-gray-100 to-transparent"></div>
            </div>
        </div>
    @endempty
</div>
