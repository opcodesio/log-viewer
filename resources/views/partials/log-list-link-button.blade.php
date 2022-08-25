<button class="log-link group" x-data="{ copied: false }" x-clipboard.raw="{{ $log->url() }}"
        x-on:click.stop="copied = true; setTimeout(() => copied = false, 1000)" title="Copy link to this log entry">
    <span x-show="!copied" class="group-hover:underline">{{ number_format($log->index) }}</span>
    <svg x-show="!copied" xmlns="http://www.w3.org/2000/svg" class="opacity-0 group-hover:opacity-75" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-link" /></svg>
    <span x-show="copied" class="text-green-600 dark:text-green-500">Copied!</span>
</button>
