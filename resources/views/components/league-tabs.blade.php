@props(['league', 'accent', 'active'])

@php
    $tabs = [
        'standings' => ['label' => 'Tabela', 'url' => \App\Support\Nav::league($league->slug)],
        'results' => ['label' => 'Rezultati', 'url' => \App\Support\Nav::leagueResults($league->slug)],
        'fixtures' => ['label' => 'Raspored', 'url' => \App\Support\Nav::leagueFixtures($league->slug)],
    ];
@endphp

<nav class="flex gap-1 border-b border-white/[0.07]" aria-label="{{ $league->name }}">
    @foreach ($tabs as $key => $tab)
        @if ($key === $active)
            <span class="px-3.5 py-2.5 text-[13.5px] font-bold {{ $accent['text'] }} border-b-2 {{ $accent['border'] }} -mb-px" aria-current="page">{{ $tab['label'] }}</span>
        @else
            <a href="{{ $tab['url'] }}" class="px-3.5 py-2.5 text-[13.5px] font-semibold text-text-muted">{{ $tab['label'] }}</a>
        @endif
    @endforeach
</nav>
