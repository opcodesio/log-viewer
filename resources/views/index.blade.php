<!doctype html>
<html lang="en" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Logs - {{ config('app.name') }}</title>

    <style>[x-cloak] { display: none !important; }</style>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    @livewireStyles
</head>
<body class="h-full px-5">
<div class="flex h-full max-h-screen">
    <div class="flex-no-shrink min-h-screen max-h-screen">
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

    <div class="flex-auto max-h-screen">

    </div>
</div>

@livewireScripts
</body>
</html>
