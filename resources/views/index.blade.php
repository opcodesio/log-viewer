<!doctype html>
<html lang="en" class="h-full bg-gray-100">
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
<body class="h-full px-5">
<div class="flex h-full max-h-screen max-w-full">
    <div class="hidden md:flex md:w-80 md:flex-col md:fixed md:inset-y-0">
        <nav class="flex flex-col h-full py-5 mr-5">
            <div class="mx-3 mb-4">
                <h1 class="font-semibold text-emerald-800 text-2xl">Better Log Viewer</h1>
                <p class="mt-0 text-gray-500 text-sm">
                    by <a href="https://www.github.com/arukompas/better-log-viewer" class="text-emerald-500">@arukompas</a>
                </p>
            </div>

            @livewire('blv::file-list')
        </nav>
    </div>

    <div class="md:pl-80 flex flex-col flex-1 min-h-screen max-h-screen max-w-full">
        @livewire('blv::log-list')
    </div>
</div>

@livewireScripts
@isset($jsPath)
    <script>{!! file_get_contents($jsPath) !!}</script>
@endisset
</body>
</html>
