<div class="relative overflow-hidden" x-cloak>
    <div class="absolute top-0 h-6 w-full bg-gradient-to-b from-gray-100 to-transparent"></div>
    <div class="relative h-full overflow-y-scroll pt-6 pb-28 pr-4">
        @foreach($files as $logFile)
            <div wire:key="log-file-{{$logFile->name}}"
                wire:click="selectFile('{{ $logFile->name }}')"
                x-on:click="selectFile('{{ $logFile->name }}')"
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
                class="mb-2 text-gray-800 rounded-md overflow-hidden transition duration-100 border-2 hover:border-emerald-600 cursor-pointer"
                x-bind:class="[selectedFileName && selectedFileName === '{{ $logFile->name }}' ? 'border-emerald-500 bg-emerald-50' : 'border-transparent bg-white']"
            >
                <div class="relative flex justify-between items-center pl-4 pr-10 py-2">
                    <p class="text-sm mr-3 whitespace-nowrap">{{ $logFile->name }}</p>
                    <span class="text-xs text-gray-500 whitespace-nowrap">{{ $logFile->sizeFormatted() }}</span>
                    <button type="button" class="file-dropdown-button"
                            x-ref="button" x-on:click.stop="toggle()" :aria-expanded="open" :aria-controls="$id('dropdown-button')"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-more" /></svg>
                    </button>
                </div>

                <div
                    x-ref="panel"
                    x-show="open"
                    x-transition.origin.top.right
                    x-on:click.outside="close($refs.button)"
                    :id="$id('dropdown-button')"
                    style="display: none;"
                    class="dropdown w-48"
                >
                    <div class="py-2">
                        <button wire:click="clearCache('{{ $logFile->name }}')" x-on:click.stop="close($refs.button)">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-database" /></svg>
                            Clear cache
                        </button>

                        <button wire:click.stop="download('{{ $logFile->name }}')">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-download" /></svg>
                            Download
                        </button>

                        <div class="w-full border-t my-2"></div>

                        <button x-on:click.stop="if (confirm('Are you sure you want to delete the log file \'{{ $logFile->name }}\'')) { $wire.call('deleteFile', '{{ $logFile->name }}') }">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-trashcan" /></svg>
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="absolute bottom-0 h-8 w-full bg-gradient-to-t from-gray-100 to-transparent"></div>
</div>
