<template>
    <div class="px-5">
        <div class="flex h-full max-h-screen max-w-full">
            <div class="hidden md:flex md:min-w-[20rem] md:flex-col md:fixed md:inset-y-0">
                <nav class="flex flex-col h-full py-5 mr-5">
                    <div class="mx-3 mb-4">
                        <h1 class="font-semibold text-emerald-800 text-2xl">Better Log Viewer</h1>
                        <p class="mt-0 text-gray-500 text-sm">
                            by <a href="https://www.github.com/arukompas/better-log-viewer" class="text-emerald-500">@arukompas</a>
                        </p>
                    </div>

                    <file-list />
                </nav>
            </div>

            <div class="md:pl-80 flex flex-col flex-1 min-h-screen max-h-screen max-w-full">
                Log list goes here
            </div>
        </div>
    </div>
</template>

<script>
import FileList from './Components/FileList.vue';
export default {
    name: 'App',
    components: { FileList },
}
</script>
