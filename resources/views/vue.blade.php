<!doctype html>
<html lang="en" class="h-full bg-gray-100">
<head>
    <title>Log Viewer @if(config('app.name')) - {{ config('app.name') }}@endif</title>
    <style>
        [v-cloak]{ display: none; }
    </style>
    <link rel="stylesheet" href="vendor/better-log-viewer/app.css">
     <script>
        window.logViewerBackendUrl = '{{ url(route("blv.index")) }}'
    </script>
    <script src="vendor/better-log-viewer/appnew.js" defer></script>
</head>
<body class="h-full">
    <div id="app" v-cloak></div>
</body>
</html>
