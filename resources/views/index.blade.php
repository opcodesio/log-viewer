<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
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
    <div class="hidden md:flex md:w-88 md:flex-col md:fixed md:inset-y-0">
        <file-list></file-list>
    </div>

    <div class="md:pl-88 flex flex-col flex-1 min-h-screen max-h-screen max-w-full">
        <log-list></log-list>
    </div>
</div>

<!-- Global LogViewer Object -->
<script>
    window.LogViewer = @json($logViewerScriptVariables);
</script>

<script src="{{ asset(mix('app.js', 'vendor/log-viewer')) }}"></script>

@include('log-viewer::icons')
</body>
</html>
