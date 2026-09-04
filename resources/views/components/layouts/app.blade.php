<!doctype html>
<html lang="sr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $metaTitle = $title ?? 'Utakmice.me';
        $metaDescription = $description ?? 'Rezultati fudbala i košarke uživo iz Crne Gore, Srbije i regiona — Prva crnogorska liga, Superliga Srbije, liga petice i Evroliga. Utakmice danas, tabele i vijesti bez čekanja.';
    @endphp
    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="alternate icon" href="/favicon.ico">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Utakmice.me">
    <meta property="og:locale" content="sr_RS">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonical ?? url()->current() }}">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">

    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => 'Utakmice.me',
        'url' => url('/'),
        'inLanguage' => 'sr',
        'description' => 'Rezultati fudbala i košarke uživo iz Crne Gore, Srbije i regiona.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>

    @stack('schema')

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
