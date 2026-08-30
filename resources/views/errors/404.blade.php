@php
    $sport = 'fudbal';
    $accent = \App\Support\Accent::classes($sport);
@endphp

<x-layouts.minimal title="404 — Utakmice.me">

    {{-- Mobile --}}
    <div class="lg:hidden flex flex-col min-h-screen pb-16">
        <div class="h-14 flex items-center justify-center border-b border-white/[0.07]">
            <div class="flex items-center gap-2">
                <x-logo />
            </div>
        </div>

        <div class="px-4 pt-8 flex flex-col gap-3.5 items-start">
            <span class="font-mono text-[84px] font-bold leading-[0.9] tracking-tighter text-panel">404</span>
            <h1 class="text-[26px] font-extrabold leading-tight tracking-tight">Ova stranica je promašila gol</h1>
            <p class="text-[15.5px] leading-relaxed text-text-muted">Link je star ili je vijest uklonjena. Rezultati i tabele su ispod, na jedan dodir.</p>
        </div>

        <div class="px-4 pt-5 flex flex-col gap-2.5">
            <a href="{{ \App\Support\Nav::home($sport) }}" class="h-13 flex items-center justify-between px-4 rounded-xl bg-text text-bg text-[15px] font-bold" style="height:52px">Naslovna<span class="font-mono text-[13px]">&rarr;</span></a>
            <a href="{{ \App\Support\Nav::scores($sport) }}" class="h-13 flex items-center justify-between px-4 rounded-xl bg-surface border border-white/[0.08] text-[15px] font-semibold" style="height:52px">Rezultati dana<span class="font-mono text-[11px] tracking-[0.1em] text-text-muted">FUDBAL &middot; KOŠARKA</span></a>
            <a href="{{ \App\Support\Nav::standings($sport) }}" class="h-13 flex items-center justify-between px-4 rounded-xl bg-surface border border-white/[0.08] text-[15px] font-semibold" style="height:52px">Tabele<span class="font-mono text-[11px] tracking-[0.1em] text-text-muted">EVROLIGA</span></a>
            <a href="{{ \App\Support\Nav::home($sport) }}" class="h-13 flex items-center justify-between px-4 rounded-xl bg-surface border border-white/[0.08] text-[15px] font-semibold" style="height:52px">Najnovija vijest<span class="font-mono text-[11px] tracking-[0.1em] text-text-muted">VIJESTI</span></a>
        </div>

        <div class="px-4 pt-6 flex flex-col gap-2.5">
            <span class="font-mono text-[10px] tracking-[0.16em] text-text-dim">ILI IDI DIREKTNO NA LIGU</span>
            <div class="flex flex-wrap gap-2">
                @foreach (\App\Support\Accent::leagues('fudbal') as $league)
                    <a href="{{ \App\Support\Nav::home('fudbal') }}" class="h-8.5 px-3.5 rounded-full flex items-center {{ $accent['tint'] }} border {{ $accent['tintBorder'] }} {{ $accent['text'] }} text-[12.5px] font-semibold" style="height:34px">{{ $league }}</a>
                @endforeach
                @php($kAccent = \App\Support\Accent::classes('kosarka'))
                @foreach (\App\Support\Accent::leagues('kosarka') as $league)
                    <a href="{{ \App\Support\Nav::scores('kosarka') }}" class="h-8.5 px-3.5 rounded-full flex items-center {{ $kAccent['tint'] }} border {{ $kAccent['tintBorder'] }} {{ $kAccent['text'] }} text-[12.5px] font-semibold" style="height:34px">{{ $league }}</a>
                @endforeach
            </div>
        </div>
    </div>
    <x-tab-bar :sport="$sport" :accent="$accent" active="404" class="lg:hidden" />

    {{-- Desktop --}}
    <div class="hidden lg:block max-w-[1000px] mx-auto py-10">
        <div class="grid grid-cols-2 gap-10 items-center">
            <div class="flex flex-col gap-4.5 items-start">
                <span class="font-mono text-[120px] font-bold leading-[0.85] tracking-tighter text-panel">404</span>
                <h1 class="text-[38px] font-extrabold leading-tight tracking-tight max-w-[18ch]">Ova stranica je promašila gol</h1>
                <p class="text-base leading-relaxed text-text-muted max-w-[46ch]">Link je star ili je vijest uklonjena. Kreni odavde &mdash; rezultati se osvežavaju svakih 30 sekundi.</p>
                <div class="flex gap-2.5">
                    <a href="{{ \App\Support\Nav::home($sport) }}" class="h-11 px-5 rounded-full bg-text text-bg text-sm font-bold flex items-center">Na naslovnu</a>
                    <a href="{{ \App\Support\Nav::scores($sport) }}" class="h-11 px-5 rounded-full bg-surface border border-white/[0.1] text-sm font-semibold flex items-center">Rezultati dana</a>
                </div>
            </div>
            <div class="flex flex-col gap-3.5">
                <div class="bg-surface border border-white/[0.07] rounded-2xl overflow-hidden">
                    <div class="px-3.5 py-3 border-b border-white/[0.07] font-mono text-[10px] font-bold tracking-[0.14em] text-text-2">NAJČITANIJE DANAS</div>
                    @foreach (collect(\App\Support\SampleData::posts('fudbal'))->take(2) as $p)
                        <a href="{{ route('post.show', $p['slug']) }}" class="flex gap-2.5 px-3.5 py-3 {{ !$loop->last ? 'border-b border-white/[0.05]' : '' }}">
                            <span class="flex-none w-14 h-11 rounded-lg img-placeholder" style="width:56px;height:44px"></span>
                            <span class="text-[13px] font-semibold leading-snug">{{ $p['title'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-layouts.minimal>
