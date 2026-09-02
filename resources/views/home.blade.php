@php
    $tabClasses = fn (string $key) => $tab === $key
        ? $accent['tint'].' '.$accent['tintBorder'].' '.$accent['text']
        : 'bg-surface border-white/[0.08] text-text-muted';
    $visibleMatches = array_slice($activeMatches, 0, 4);
    $hasMoreMatches = count($activeMatches) > 4;
@endphp

<x-layouts.app :sport="$sport" :accent="$accent" :active="$active" :title="'Naslovna — Utakmice.me'">

    {{-- Mobile --}}
    <div class="lg:hidden">
        <div class="px-4 pt-3 flex gap-2">
            <a href="{{ \App\Support\Nav::home($sport, 'uzivo') }}"
               class="flex-1 h-8.5 rounded-full flex items-center justify-center gap-1.5 border font-mono text-[10px] font-bold tracking-[0.1em] {{ $tabClasses('uzivo') }}" style="height:34px">
                <span class="w-1.5 h-1.5 rounded-full bg-live animate-live"></span>
                UŽIVO {{ count($liveTabs['uzivo']) }}
            </a>
            <a href="{{ \App\Support\Nav::home($sport, 'danas') }}"
               class="flex-1 h-8.5 rounded-full flex items-center justify-center border font-mono text-[10px] font-bold tracking-[0.1em] {{ $tabClasses('danas') }}" style="height:34px">DANAS</a>
            <a href="{{ \App\Support\Nav::home($sport, 'sutra') }}"
               class="flex-1 h-8.5 rounded-full flex items-center justify-center border font-mono text-[10px] font-bold tracking-[0.1em] {{ $tabClasses('sutra') }}" style="height:34px">SUTRA</a>
        </div>

        <div class="px-4 pt-3.5 flex flex-col gap-2">
            @forelse ($visibleMatches as $m)
                @php
                    $finished = ! $m['live'] && $m['hs'] !== '–';
                    $homeWins = $finished && (int) $m['hs'] > (int) $m['as'];
                    $awayWins = $finished && (int) $m['as'] > (int) $m['hs'];
                @endphp
                <a href="{{ isset($m['id']) ? \App\Support\Nav::match($m['id']) : \App\Support\Nav::scores($sport) }}" class="bg-surface border border-white/[0.07] rounded-2xl p-3.5 flex items-center gap-3.5">
                    <div class="w-11.5 flex-none flex flex-col items-center gap-0.5" style="width:46px">
                        <span class="font-mono text-[10px] font-bold {{ $m['live'] ? 'text-live-text' : 'text-text-muted' }}">{{ $m['status'] }}</span>
                        <span class="font-mono text-[7.5px] tracking-[0.1em] text-text-dim">{{ $m['league'] }}</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <div class="flex justify-between {{ $awayWins ? 'text-text-muted' : '' }}"><span class="text-sm {{ $awayWins ? '' : 'font-semibold' }}">{{ $m['home'] }}</span><span class="font-mono text-sm font-bold {{ $m['live'] ? 'text-live-text' : '' }}">{{ $m['hs'] }}</span></div>
                        <div class="flex justify-between {{ $homeWins ? 'text-text-muted' : '' }}"><span class="text-sm {{ $homeWins ? '' : 'font-semibold' }}">{{ $m['away'] }}</span><span class="font-mono text-sm font-bold {{ $m['live'] ? 'text-live-text' : '' }}">{{ $m['as'] }}</span></div>
                    </div>
                </a>
            @empty
                @if ($openingRound)
                    <div class="border border-dashed {{ $accent['tintBorder'] }} rounded-2xl p-4.5 flex flex-col gap-3">
                        <div class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full {{ $accent['bg'] }}"></span>
                            <span class="font-mono text-[10px] font-bold tracking-[0.14em] {{ $accent['text'] }}">PRVO KOLO SEZONE &middot; {{ strtoupper($openingRound['label']) }}</span>
                        </div>
                        <div class="flex flex-col gap-2.5">
                            @foreach (array_slice($openingRound['matches'], 0, 4) as $m)
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex flex-col gap-0.5 min-w-0">
                                        <span class="text-sm font-semibold truncate">{{ $m['home'] }}</span>
                                        <span class="text-sm font-semibold truncate">{{ $m['away'] }}</span>
                                    </div>
                                    <div class="flex flex-col items-end flex-none gap-0.5">
                                        <span class="font-mono text-[10px] text-text-muted">{{ $m['status'] }}</span>
                                        <span class="font-mono text-[8px] tracking-[0.08em] text-text-dim">{{ $m['league'] }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <a href="{{ \App\Support\Nav::scores($sport, $openingRound['date']->format('Y-m-d')) }}" class="h-11 rounded-full flex items-center justify-center {{ $accent['bg'] }} text-bg text-sm font-bold">Vidi ceo raspored 1. kola</a>
                    </div>
                @else
                    <div class="border border-dashed border-white/[0.12] rounded-2xl p-4.5 flex flex-col gap-2.5">
                        <span class="text-base font-bold">Trenutno nema mečeva uživo</span>
                        <span class="text-sm leading-relaxed text-text-muted">Sledeći termini su u tabu DANAS.</span>
                        <a href="{{ \App\Support\Nav::home($sport, 'danas') }}" class="h-11 rounded-full flex items-center justify-center {{ $accent['bg'] }} text-bg text-sm font-bold">Vidi današnji program</a>
                    </div>
                @endif
            @endforelse
            @if ($hasMoreMatches)
                <a href="{{ \App\Support\Nav::scores($sport) }}" class="h-11 rounded-full flex items-center justify-center bg-surface border border-white/[0.08] text-text-2 text-sm font-semibold">Vidi sve rezultate &rsaquo;</a>
            @endif
        </div>

        <div class="px-4 pt-4.5 flex flex-col gap-3">
            <div class="flex items-center gap-2.5">
                <span class="w-[3px] h-3.5 rounded {{ $accent['bg'] }}"></span>
                <span class="font-mono text-[10px] font-bold tracking-[0.16em] text-text-2">IZDVOJENO</span>
            </div>
            <x-hero-post :post="$hero" :accent="$accent" />
            @foreach ($secondary as $post)
                <x-post-list-item :post="$post" />
            @endforeach
            @foreach ($latest as $post)
                <x-post-list-item :post="$post" />
            @endforeach
        </div>
    </div>

    {{-- Desktop --}}
    <div class="hidden lg:block max-w-[1120px] mx-auto">
        <div class="px-7 py-5 border-b border-white/[0.07] flex flex-col gap-3.5">
            <div class="flex items-center justify-between gap-5">
                <div class="flex items-center gap-2">
                    <a href="{{ \App\Support\Nav::home($sport, 'uzivo') }}"
                       class="h-8.5 px-3.5 rounded-full flex items-center gap-2 border font-mono text-[10.5px] font-bold tracking-[0.12em] {{ $tabClasses('uzivo') }}" style="height:34px">
                        <span class="w-1.5 h-1.5 rounded-full bg-live animate-live"></span>
                        UŽIVO
                        <span class="min-w-[18px] h-4.5 px-1.5 rounded-full bg-white/[0.1] flex items-center justify-center text-[9.5px]" style="height:18px">{{ count($liveTabs['uzivo']) }}</span>
                    </a>
                    <a href="{{ \App\Support\Nav::home($sport, 'danas') }}"
                       class="h-8.5 px-4 rounded-full flex items-center border font-mono text-[10.5px] font-bold tracking-[0.12em] {{ $tabClasses('danas') }}" style="height:34px">DANAS</a>
                    <a href="{{ \App\Support\Nav::home($sport, 'sutra') }}"
                       class="h-8.5 px-4 rounded-full flex items-center border font-mono text-[10.5px] font-bold tracking-[0.12em] {{ $tabClasses('sutra') }}" style="height:34px">SUTRA</a>
                </div>
                <a href="{{ \App\Support\Nav::scores($sport) }}" class="font-mono text-[10.5px] tracking-[0.12em] {{ $accent['text'] }}">SVI REZULTATI &rsaquo;</a>
            </div>

            @if (count($activeMatches) > 0)
                <div class="grid grid-cols-4 gap-3">
                    @foreach ($visibleMatches as $m)
                        @php
                            $finished = ! $m['live'] && $m['hs'] !== '–';
                            $homeWins = $finished && (int) $m['hs'] > (int) $m['as'];
                            $awayWins = $finished && (int) $m['as'] > (int) $m['hs'];
                        @endphp
                        <a href="{{ isset($m['id']) ? \App\Support\Nav::match($m['id']) : \App\Support\Nav::scores($sport) }}" class="bg-surface border border-white/[0.07] rounded-2xl p-3.5 flex flex-col gap-2.5">
                            <div class="flex justify-between items-center">
                                <span class="font-mono text-[9px] tracking-[0.12em] text-text-muted">{{ $m['league'] }}</span>
                                <span class="font-mono text-[9.5px] font-bold tracking-[0.1em] {{ $m['live'] ? 'text-live-text' : 'text-text-muted' }}">{{ $m['status'] }}</span>
                            </div>
                            <div class="flex justify-between items-center {{ $awayWins ? 'text-text-muted' : '' }}"><span class="text-sm {{ $awayWins ? '' : 'font-semibold' }}">{{ $m['home'] }}</span><span class="font-mono text-[15px] font-bold {{ $m['live'] ? 'text-live-text' : '' }}">{{ $m['hs'] }}</span></div>
                            <div class="flex justify-between items-center {{ $homeWins ? 'text-text-muted' : '' }}"><span class="text-sm {{ $homeWins ? '' : 'font-semibold' }}">{{ $m['away'] }}</span><span class="font-mono text-[15px] font-bold {{ $m['live'] ? 'text-live-text' : '' }}">{{ $m['as'] }}</span></div>
                        </a>
                    @endforeach
                </div>
                @if ($hasMoreMatches)
                    <a href="{{ \App\Support\Nav::scores($sport) }}" class="self-start h-9 px-4 rounded-full flex items-center bg-surface border border-white/[0.08] text-text-2 text-[13px] font-semibold">Vidi sve rezultate &rsaquo;</a>
                @endif
            @elseif ($openingRound)
                <div class="flex flex-col gap-3">
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full {{ $accent['bg'] }}"></span>
                        <span class="font-mono text-[10.5px] font-bold tracking-[0.14em] {{ $accent['text'] }}">PRVO KOLO SEZONE &middot; {{ strtoupper($openingRound['label']) }}</span>
                    </div>
                    <div class="grid grid-cols-4 gap-3">
                        @foreach (array_slice($openingRound['matches'], 0, 4) as $m)
                            <div class="bg-surface border border-white/[0.07] rounded-2xl p-3.5 flex flex-col gap-2.5">
                                <div class="flex justify-between items-center">
                                    <span class="font-mono text-[9px] tracking-[0.12em] text-text-muted">{{ $m['league'] }}</span>
                                    <span class="font-mono text-[9.5px] font-bold tracking-[0.1em] text-text-muted">{{ $m['status'] }}</span>
                                </div>
                                <span class="text-sm font-semibold">{{ $m['home'] }}</span>
                                <span class="text-sm font-semibold">{{ $m['away'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <a href="{{ \App\Support\Nav::scores($sport, $openingRound['date']->format('Y-m-d')) }}" class="self-start h-9 px-4 rounded-full flex items-center {{ $accent['bg'] }} text-bg text-[13px] font-bold">Vidi ceo raspored 1. kola &rsaquo;</a>
                </div>
            @else
                <div class="border border-dashed border-white/[0.12] rounded-2xl p-5.5 flex items-center justify-between gap-5">
                    <div class="flex flex-col gap-1.5">
                        <span class="text-base font-bold">Trenutno nema mečeva uživo</span>
                        <span class="text-sm text-text-muted">Sledeći termini su u tabu DANAS. Uključi obaveštenja i javimo ti kad prvi meč počne.</span>
                    </div>
                    <div class="flex gap-2.5 flex-none">
                        <a href="{{ \App\Support\Nav::home($sport, 'danas') }}" class="h-10 px-4.5 rounded-full flex items-center {{ $accent['bg'] }} text-bg text-[13.5px] font-bold">Vidi današnji program</a>
                        <span class="h-10 px-4.5 rounded-full flex items-center bg-surface border border-white/[0.1] text-text-2 text-[13.5px] font-semibold">Obaveštenja</span>
                    </div>
                </div>
            @endif
        </div>

        <div class="grid gap-7 px-7 py-6" style="grid-template-columns:1fr 320px">
            <div class="flex flex-col gap-5.5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="w-[3px] h-3.5 rounded {{ $accent['bg'] }}"></span>
                        <span class="font-mono text-[10.5px] font-bold tracking-[0.16em] text-text-2">IZDVOJENO</span>
                    </div>
                    <a href="{{ \App\Support\Nav::home($sport) }}" class="font-mono text-[10px] tracking-[0.12em] text-text-muted">ARHIVA &rsaquo;</a>
                </div>

                <div class="grid gap-4" style="grid-template-columns:1.35fr 1fr">
                    <a href="{{ route('post.show', $hero['slug']) }}" class="bg-surface border border-white/[0.07] rounded-2xl overflow-hidden flex flex-col">
                        <div class="p-5.5 flex flex-col gap-3">
                            <span class="self-start font-mono text-[9.5px] font-bold tracking-[0.12em] px-2.5 py-1 rounded {{ $accent['tint'] }} {{ $accent['text'] }}">GLAVNA VIJEST</span>
                            <h1 class="text-[30px] font-extrabold leading-tight tracking-tight text-balance">{{ $hero['title'] }}</h1>
                            <p class="text-[15.5px] leading-relaxed text-text-muted">{{ $hero['lead'] }}</p>
                            <span class="font-mono text-[9.5px] tracking-[0.1em] text-text-dim">{{ strtoupper($hero['meta']) }} &middot; {{ $hero['read_minutes'] }} MIN ČITANJA</span>
                        </div>
                    </a>

                    <div class="flex flex-col gap-4">
                        @foreach ($secondary as $post)
                            <a href="{{ route('post.show', $post['slug']) }}" class="flex-1 bg-surface border border-white/[0.07] rounded-2xl overflow-hidden flex flex-col">
                                <div class="p-4 flex flex-col gap-2">
                                    <span class="font-mono text-[9px] tracking-[0.12em] {{ $accent['text'] }}">{{ strtoupper($post['league']) }}</span>
                                    <span class="text-[17px] font-bold leading-snug tracking-tight">{{ $post['title'] }}</span>
                                    <span class="font-mono text-[9.5px] tracking-[0.1em] text-text-dim">{{ strtoupper($post['meta']) }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="h-px bg-white/[0.07]"></div>

                <div class="flex items-center gap-2.5">
                    <span class="w-[3px] h-3.5 rounded {{ $accent['bg'] }}"></span>
                    <span class="font-mono text-[10.5px] font-bold tracking-[0.16em] text-text-2">NAJNOVIJE</span>
                </div>
                <div class="grid grid-cols-2 gap-x-7 gap-y-4">
                    @foreach ($latest as $post)
                        <a href="{{ route('post.show', $post['slug']) }}" class="flex flex-col gap-1.5">
                            <span class="text-[15px] font-bold leading-snug">{{ $post['title'] }}</span>
                            <span class="font-mono text-[9.5px] tracking-[0.1em] text-text-dim">{{ strtoupper($post['meta']) }} &middot; {{ strtoupper($post['league']) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col gap-3.5">
                <div class="bg-surface border border-white/[0.07] rounded-2xl overflow-hidden">
                    <div class="px-3.5 py-3 border-b border-white/[0.07] flex justify-between items-center">
                        <span class="font-mono text-[10px] font-bold tracking-[0.14em] text-text-2">TABELA</span>
                        <a href="{{ \App\Support\Nav::standings($sport) }}" class="font-mono text-[9.5px] tracking-[0.1em] text-text-muted">CELA &rsaquo;</a>
                    </div>
                    @foreach ($miniStandings as $row)
                        <div class="px-3.5 py-2.5 flex justify-between {{ !$loop->last ? 'border-b border-white/[0.05]' : '' }}">
                            <span class="text-[13.5px] {{ $row['pos'] === 1 ? 'font-bold' : '' }}"><span class="font-mono mr-2.5 {{ $row['pos'] === 1 ? $accent['text'] : 'text-text-muted' }}">{{ $row['pos'] }}</span>{{ $row['team'] }}</span>
                            <span class="font-mono text-[12.5px] font-bold">{{ $row['points'] }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="bg-surface border border-white/[0.07] rounded-2xl overflow-hidden">
                    <div class="px-3.5 py-3 border-b border-white/[0.07] font-mono text-[10px] font-bold tracking-[0.14em] text-text-2">NAJČITANIJE DANAS</div>
                    @foreach ($mostRead as $post)
                        <a href="{{ route('post.show', $post['slug']) }}" class="px-3.5 py-3 flex items-center gap-3 {{ !$loop->last ? 'border-b border-white/[0.05]' : '' }}">
                            <span class="font-mono text-[15px] font-bold text-panel-2" style="color:#2A323C">{{ sprintf('%02d', $loop->iteration) }}</span>
                            <span class="text-[13px] font-semibold leading-snug">{{ $post['title'] }}</span>
                        </a>
                    @endforeach
                </div>

                <div class="border border-dashed border-white/[0.12] rounded-2xl p-4 flex flex-col gap-2.5">
                    <span class="font-mono text-[9px] tracking-[0.14em] text-text-dim">NEWSLETTER</span>
                    <span class="text-[15px] font-bold leading-snug">Rezultati dana u tvoj inbox, svako jutro u 8</span>
                    <span class="h-10 rounded-full bg-surface border border-white/[0.1] flex items-center px-3.5 text-text-dim text-[13px]">tvoj@email.com</span>
                    <span class="h-10 rounded-full bg-text text-bg flex items-center justify-center text-[13.5px] font-bold">Prijavi se</span>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
