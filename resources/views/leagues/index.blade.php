@php
    $sport = 'fudbal';
    $accent = \App\Support\Accent::classes($sport);
@endphp

<x-layouts.app :sport="$sport" :accent="$accent" active="leagues" title="Lige i takmičenja — Utakmice.me" description="Sve lige i takmičenja koje pratimo — fudbal i košarka, po zemljama.">
    <div class="max-w-[1120px] mx-auto px-4 lg:px-7 py-5 lg:py-7 flex flex-col gap-6">
        <h1 class="text-2xl font-extrabold tracking-tight">Lige i takmičenja</h1>

        @if ($bySport->isEmpty())
            <div class="border border-dashed border-white/[0.12] rounded-2xl p-8 text-center text-text-muted text-sm">
                Trenutno nema aktivnih liga.
            </div>
        @else
            @foreach ($bySport as $block)
                @php $sportAccent = \App\Support\Accent::classes($block['sport']); @endphp
                <div class="flex flex-col gap-5">
                    <div class="flex items-center gap-2.5">
                        <span class="w-1 h-5 rounded-[2px] {{ $sportAccent['bg'] }}"></span>
                        <h2 class="text-lg font-extrabold tracking-tight">{{ $block['label'] }}</h2>
                    </div>

                    @foreach ($block['byCountry'] as $country => $leagues)
                        <section class="flex flex-col gap-2.5">
                            <span class="font-mono text-[10.5px] tracking-[0.1em] text-text-dim uppercase">{{ $country }}</span>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach ($leagues as $league)
                                    @php $live = (int) ($liveByLeague[$league->id] ?? 0); @endphp
                                    <a href="{{ \App\Support\Nav::league($league->slug) }}"
                                       class="min-w-0 flex items-center gap-3 px-4 py-3 bg-surface border border-white/[0.07] rounded-2xl transition-colors hover:bg-[#171B21]">
                                        <span class="w-9 h-9 rounded-xl bg-surface-2 border border-white/[0.07] flex-none overflow-hidden flex items-center justify-center">
                                            <x-league-icon :icon="\App\Support\Accent::leagueIcon($league->name)" class="w-6 h-4.5" />
                                        </span>

                                        <span class="min-w-0 flex-1">
                                            <span class="block text-[13.5px] font-bold whitespace-nowrap overflow-hidden text-ellipsis">{{ $league->name }}</span>
                                            <span class="block text-[11.5px] text-text-muted">{{ $league->teams_count }} {{ \App\Support\Plural::sr($league->teams_count, 'klub', 'kluba', 'klubova') }}</span>
                                        </span>

                                        @if ($live > 0)
                                            <span class="shrink-0 flex items-center gap-1.5 font-mono text-[10.5px] font-bold text-live-text">
                                                <span class="inline-block w-[5px] h-[5px] rounded-full bg-live animate-live"></span>
                                                {{ $live }}
                                            </span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            @endforeach
        @endif
    </div>
</x-layouts.app>
