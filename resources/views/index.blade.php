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
    @scan-files.window="$store.fileViewer.initScanCheck('{{ route('blv.is-scan-required') }}', '{{ route('blv.scan-files') }}')"
    x-init="$nextTick(() => {
        $store.fileViewer.reset();
        $dispatch('scan-files');
        @if(isset($selectedFile)) $store.fileViewer.foldersOpen.push('{{ $selectedFile->subFolderIdentifier() }}'); @endif
    })"
>
<div class="flex h-full max-h-screen max-w-full">
    <div class="hidden md:flex md:w-88 md:flex-col md:fixed md:inset-y-0">
        @livewire('log-viewer::file-list', ['selectedFileIdentifier' => isset($selectedFile) ? $selectedFile->identifier : null])
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
