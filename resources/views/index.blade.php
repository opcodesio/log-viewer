<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Logs - {{ config('app.name') }}</title>

    <style>[x-cloak] { display: none !important; }</style>
    @isset($cssPath)
        <style>{!! file_get_contents($cssPath) !!}</style>
    @endisset
    @livewireStyles
</head>
<body class="h-full px-5 bg-gray-100 dark:bg-gray-900"
    x-data="{
        selectedFileIdentifier: @isset($selectedFile) '{{ $selectedFile->identifier }}' @else null @endisset,
        selectFile(name) {
            if (name && name === this.selectedFileIdentifier) {
                this.selectedFileIdentifier = null;
            } else {
                this.selectedFileIdentifier = name;
            }
            this.$dispatch('file-selected', this.selectedFileIdentifier);
        }
    }"
    x-init="$nextTick(() => { $store.fileViewer.reset(); @if(isset($selectedFile)) $store.fileViewer.foldersOpen.push('{{ $selectedFile->subFolderIdentifier() }}') @endif })"
>
<div class="flex h-full max-h-screen max-w-full">
    <div class="hidden md:flex md:w-88 md:flex-col md:fixed md:inset-y-0">
        <nav class="flex flex-col h-full py-5">
            <div class="mx-3 mb-4">
                <h1 class="font-semibold text-emerald-800 dark:text-emerald-600 text-2xl flex items-center">
                    Log Viewer
                    <a href="https://www.github.com/opcodesio/log-viewer" target="_blank" class="ml-3 text-gray-400 hover:text-emerald-800 dark:hover:text-emerald-600 p-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><use href="#icon-github" /></svg>
                    </a>
                </h1>
                @if($backUrl = config('log-viewer.back_to_system_url'))
                    <a href="{{ $backUrl }}" class="inline-flex items-center text-sm text-gray-400 hover:text-emerald-800 dark:hover:text-emerald-600 mt-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1.5" viewBox="0 0 20 20" fill="currentColor"><use href="#icon-arrow-left" /></svg>
                        {{ config('log-viewer.back_to_system_label') ?? 'Back to '.config('app.name') }}
                    </a>
                @endif
            </div>

            @livewire('log-viewer::file-list', ['selectedFileIdentifier' => $selectedFile?->identifier])
        </nav>
    </div>

    <div class="md:pl-88 flex flex-col flex-1 min-h-screen max-h-screen max-w-full">
        @livewire('log-viewer::log-list')
    </div>
</div>

@livewireScripts
@isset($jsPath)
    <script>{!! file_get_contents($jsPath) !!}</script>
@endisset

@include('log-viewer::icons')
</body>
</html>
