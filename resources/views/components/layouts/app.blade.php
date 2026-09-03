<!doctype html>
<html lang="sr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Utakmice.me' }}</title>
    @isset($description)
        <meta name="description" content="{{ $description }}">
    @endisset
    @isset($canonical)
        <link rel="canonical" href="{{ $canonical }}">
    @endisset
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="alternate icon" href="/favicon.ico">

    @production
        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-QY76S4TYR6"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', 'G-QY76S4TYR6');
        </script>
    @endproduction

    @fonts

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="bg-bg text-text font-sans antialiased" data-push="{{ \App\Support\WebPush::configured() ? '1' : '0' }}">
    <div class="min-h-screen flex flex-col">
        <x-site-header :sport="$sport" :accent="$accent" :active="$active" />

        <main class="flex-1">
            {{ $slot }}
        </main>

        <x-site-footer :sport="$sport" :accent="$accent" />

        @if ($active === 'scores')
            <x-tab-bar :sport="$sport" :accent="$accent" />
        @endif
    </div>
</body>
</html>
