<button title="Share this page" class="relative ml-5 p-2 text-gray-400 group hover:text-gray-500 cursor-pointer"
     x-data="{ copied: false }" x-clipboard="window.location.href" x-on:click.stop="copied = true; setTimeout(() => copied = false, 1000)">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-share" /></svg>
    <span class="absolute -top-4 -right-3 px-2 py-0.5 bg-gray-100 rounded text-xs hidden group-hover:block whitespace-nowrap"><span x-show="!copied">Share this page</span><span x-show="copied" class="text-emerald-600">Copied!</span></span>
</button>
