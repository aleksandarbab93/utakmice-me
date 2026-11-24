@props(['items'])

@push('schema')
    <script type="application/ld+json">
    {!! json_encode([
        '@@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => collect($items)->map(fn (string $url, string $name) => ['name' => $name, 'item' => $url])
            ->values()
            ->map(fn (array $item, int $i) => ['@type' => 'ListItem', 'position' => $i + 1] + $item)
            ->all(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
    </script>
@endpush
