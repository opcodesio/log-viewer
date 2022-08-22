<div class="py-1 ml-2 text-gray-700 dark:text-gray-300" @theme-updated.window="location.reload()">
    Theme: <select wire:model="theme" class="bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 px-1 font-normal outline-emerald-500">
        <option value="light">Light</option>
        <option value="dark">Dark</option>
        <option value="system">System</option>
    </select>
</div>
