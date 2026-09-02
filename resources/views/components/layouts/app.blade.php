<!doctype html>
<html lang="sr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Utakmice.me' }}</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="alternate icon" href="/favicon.ico">

    @fonts

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="bg-bg text-text font-sans antialiased">
    <div class="min-h-screen flex flex-col">
        <x-site-header :sport="$sport" :accent="$accent" :active="$active" />

        <main class="flex-1">
            {{ $slot }}
        </main>

        <x-site-footer :sport="$sport" :accent="$accent" />

        <x-tab-bar :sport="$sport" :accent="$accent" :active="$active" />
    </div>
</body>
</html>
