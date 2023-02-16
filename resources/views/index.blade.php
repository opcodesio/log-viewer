<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{--    <link rel="shortcut icon" href="{{ asset('/vendor/log-viewer/img/favicon.png') }}">--}}

    <title>Logs{{ config('app.name') ? ' - ' . config('app.name') : '' }}</title>

    <!-- Style sheets-->
    {{--    <link rel="preconnect" href="https://fonts.bunny.net">--}}
    {{--    <link href="https://fonts.bunny.net/css?family=figtree:300,400,500,600" rel="stylesheet" />--}}
    <link href="{{ asset(mix('app.css', 'vendor/log-viewer')) }}" rel="stylesheet">
</head>

<body class="h-full px-5 bg-gray-100 dark:bg-gray-900">
<div id="log-viewer" class="flex h-full max-h-screen max-w-full">
    <router-view></router-view>
</div>

<div class="absolute bottom-4 right-4 flex items-center">
    <p class="text-xs text-gray-400 dark:text-gray-500 mr-20">
        <span>Version: <span class="font-semibold">{{ \Opcodes\LogViewer\Facades\LogViewer::version() }}</span></span>
    </p>
    <div>
        <script data-name="BMC-Widget" data-cfasync="false"
                src="https://cdnjs.buymeacoffee.com/1.0.0/widget.prod.min.js" data-id="arunas"
                data-description="Support me on Buy me a coffee!" data-message="" data-color="#40DCA5"
                data-position="Right" data-x_margin="18" data-y_margin="18"></script>
    </div>
</div>

<!-- Global LogViewer Object -->
<script>
    window.LogViewer = @json($logViewerScriptVariables);
</script>
<script src="{{ asset(mix('app.js', 'vendor/log-viewer')) }}"></script>
</body>
</html>
