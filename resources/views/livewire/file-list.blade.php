<div class="relative overflow-hidden" wire:init="loadFiles">
    <div class="absolute top-0 h-6 w-full bg-gradient-to-b from-gray-100 to-transparent"></div>
    <div class="relative h-full overflow-y-scroll py-6">
        @foreach($files as $logFile)
            <div wire:key="log-file-{{$logFile->name}}"
                wire:click="selectFile('{{ $logFile->name }}')"
                x-data="{
                    open: false,
                    toggle() {
                        if (this.open) { return this.close() }
                        this.$refs.button.focus()
                        this.open = true
                    },
                    close(focusAfter) {
                        if (! this.open) { return }
                        this.open = false
                        focusAfter && focusAfter.focus()
                    }
                }"
                x-on:keydown.escape.prevent.stop="close($refs.button)"
                x-on:focusin.window="! $refs.panel.contains($event.target) && close()"
                x-id="['dropdown-button']"
                class="mb-2 text-gray-800 rounded-md bg-white overflow-hidden transition duration-100 border-2 border-transparent hover:border-emerald-600 cursor-pointer @if($file === $logFile->name) border-emerald-500 @endif"
            >
                <div
                    class="relative flex justify-between items-center pl-4 pr-10 py-2">
                    <p class="text-sm mr-3 whitespace-nowrap">{{ $logFile->name }}</p>
                    <span class="text-xs text-gray-500 whitespace-nowrap">{{ $logFile->sizeFormatted() }}</span>
                    <button type="button" class="absolute top-0 right-0 bottom-0 w-8 flex items-center justify-center border-l-2 border-transparent text-gray-500 hover:border-emerald-600 hover:bg-emerald-50 transition duration-200"
                            x-ref="button" x-on:click.stop="toggle()" :aria-expanded="open" :aria-controls="$id('dropdown-button')"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" /></svg>
                    </button>
                </div>

                <div
                    x-ref="panel"
                    x-show="open"
                    x-transition.origin.top.left
                    x-on:click.outside="close($refs.button)"
                    :id="$id('dropdown-button')"
                    style="display: none;"
                    class="absolute z-20 right-0 mt-2 w-40 overflow-hidden rounded-md bg-white border-2 border-emerald-500"
                >
                    <div>
                        <button wire:click.stop="download('{{ $logFile->name }}')" class="block flex items-center w-full px-4 py-2 text-left text-sm hover:bg-gray-50 disabled:text-gray-500" >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 opacity-75 inline-block" viewBox="0 0 20 20" fill="currentColor">
                               <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                            Download
                        </button>

                        <button x-on:click.stop="if (confirm('Are you sure you want to delete the log file \'{{ $logFile->name }}\'')) { $wire.call('deleteFile', '{{ $logFile->name }}') }" class="block flex items-center w-full px-4 py-2 text-left text-sm hover:bg-rose-50 disabled:text-gray-500" >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 opacity-75 inline-block" viewBox="0 0 20 20" fill="currentColor">
                               <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="absolute bottom-0 h-8 w-full bg-gradient-to-t from-gray-100 to-transparent"></div>
</div>
