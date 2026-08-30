<x-layouts.app :sport="$sport" :accent="$accent" :active="$active" :title="$match['home']['name'].' - '.$match['away']['name'].' — Utakmice.me'">
    <div class="max-w-[720px] mx-auto px-4 lg:px-0 py-5 lg:py-8 flex flex-col gap-5">

        {{-- Breadcrumb --}}
        <div class="flex items-center gap-2 font-mono text-[10px] tracking-[0.12em] text-text-dim">
            <a href="{{ \App\Support\Nav::home('fudbal') }}" class="{{ $accent['text'] }}">FUDBAL</a>
            <span>&rsaquo;</span>
            <span>{{ $match['flag'] }} {{ strtoupper($match['league']) }}</span>
            @if ($match['round'])
                <span>&rsaquo;</span>
                <span>{{ strtoupper($match['round']) }}</span>
            @endif
        </div>

        {{-- Score header --}}
        <div class="bg-surface border border-white/[0.07] rounded-2xl p-5 lg:p-7 flex flex-col gap-5">
            <div class="grid items-center gap-3" style="grid-template-columns:1fr auto 1fr">
                <div class="flex flex-col items-center gap-2.5 text-center">
                    <span class="w-14 h-14 rounded-full bg-surface-2 border border-white/[0.08] flex items-center justify-center text-base font-bold text-text-2">{{ $match['home']['initials'] }}</span>
                    <span class="text-sm lg:text-[15px] font-bold">{{ $match['home']['name'] }}</span>
                </div>

                <div class="flex flex-col items-center gap-1.5 px-3">
                    @if ($match['status'] === 'scheduled')
                        <span class="font-mono text-[11px] text-text-muted">{{ $match['kickoff'] }}</span>
                        <span class="font-mono text-2xl font-bold text-text-dim">&ndash; : &ndash;</span>
                    @else
                        <span class="font-mono text-4xl lg:text-5xl font-bold {{ $match['status'] === 'live' ? 'text-live-text' : '' }}">{{ $match['home_score'] }}&nbsp;-&nbsp;{{ $match['away_score'] }}</span>
                        <span class="font-mono text-[10.5px] font-bold tracking-[0.08em] {{ $match['status'] === 'live' ? 'text-live-text' : 'text-text-muted' }} flex items-center gap-1.5">
                            @if ($match['status'] === 'live')
                                <span class="w-1.5 h-1.5 rounded-full bg-live animate-live"></span>
                            @endif
                            {{ strtoupper($match['statusLabel']) }}
                        </span>
                    @endif
                </div>

                <div class="flex flex-col items-center gap-2.5 text-center">
                    <span class="w-14 h-14 rounded-full bg-surface-2 border border-white/[0.08] flex items-center justify-center text-base font-bold text-text-2">{{ $match['away']['initials'] }}</span>
                    <span class="text-sm lg:text-[15px] font-bold">{{ $match['away']['name'] }}</span>
                </div>
            </div>

            @if ($match['venue'] || $match['referee'])
                <div class="pt-4 border-t border-white/[0.07] flex flex-wrap justify-center gap-x-4 gap-y-1 font-mono text-[10px] tracking-[0.08em] text-text-dim">
                    @if ($match['venue'])
                        <span>{{ strtoupper($match['venue']) }}</span>
                    @endif
                    @if ($match['referee'])
                        <span>SUDIJA: {{ strtoupper($match['referee']) }}</span>
                    @endif
                </div>
            @endif
        </div>

        @if (empty($match['halves']) && empty($match['stats']))
            <div class="border border-dashed border-white/[0.12] rounded-2xl p-6 text-center text-text-muted text-sm">
                @if ($match['status'] === 'scheduled')
                    Meč još nije počeo — detalji toka meča biće dostupni nakon početka.
                @else
                    Detalji toka meča trenutno nisu dostupni.
                @endif
            </div>
        @else
            <div class="flex gap-2" id="match-tabs">
                <button data-match-tab="tok" class="match-tab-btn is-active-match-tab h-8.5 px-4 rounded-full flex items-center font-mono text-[10.5px] font-bold tracking-[0.1em] bg-white/[0.1]" style="height:34px">TOK MEČA</button>
                <button data-match-tab="statistika" class="match-tab-btn h-8.5 px-4 rounded-full flex items-center font-mono text-[10.5px] font-bold tracking-[0.1em] bg-surface border border-white/[0.08] text-text-muted" style="height:34px">STATISTIKA</button>
            </div>
        @endif

        {{-- Tok meča --}}
        @if (! empty($match['halves']))
            <div class="flex flex-col gap-3" data-match-panel="tok">
                <div class="bg-surface border border-white/[0.07] rounded-2xl overflow-hidden">
                    @foreach ($match['halves'] as $half)
                        <div class="px-3.5 py-2 bg-white/[0.03] border-b border-white/[0.05] flex items-center justify-between">
                            <span class="font-mono text-[9.5px] font-bold tracking-[0.1em] text-text-dim">{{ strtoupper($half['label']) }}</span>
                            @if ($half['score'])
                                <span class="font-mono text-[9.5px] font-bold text-text-dim">{{ $half['score'] }}</span>
                            @endif
                        </div>

                        @forelse ($half['events'] as $ev)
                            <div class="grid items-center gap-2 px-3.5 py-2.5 border-b border-white/[0.05] last:border-0" style="grid-template-columns:1fr 44px 1fr">
                                <div class="flex items-center justify-end gap-2 text-right {{ $ev['side'] !== 'home' ? 'invisible' : '' }}">
                                    <div class="flex flex-col leading-tight">
                                        <span class="text-[13px] font-semibold">{{ $ev['player'] }}</span>
                                        @if ($ev['subtitle'])
                                            <span class="font-mono text-[9px] text-text-dim">{{ $ev['subtitle'] }}</span>
                                        @endif
                                    </div>
                                    <x-match-event-icon :icon="$ev['icon']" />
                                </div>

                                <span class="font-mono text-[10px] text-text-dim text-center">{{ $ev['elapsed'] }}{{ $ev['extra'] ? '+'.$ev['extra'] : '' }}'</span>

                                <div class="flex items-center gap-2 {{ $ev['side'] !== 'away' ? 'invisible' : '' }}">
                                    <x-match-event-icon :icon="$ev['icon']" />
                                    <div class="flex flex-col leading-tight">
                                        <span class="text-[13px] font-semibold">{{ $ev['player'] }}</span>
                                        @if ($ev['subtitle'])
                                            <span class="font-mono text-[9px] text-text-dim">{{ $ev['subtitle'] }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="px-3.5 py-3 text-center text-[12px] text-text-dim">Nema zabilježenih događaja.</div>
                        @endforelse
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Statistika --}}
        @if (! empty($match['stats']))
            <div class="hidden" data-match-panel="statistika">
                <div class="bg-surface border border-white/[0.07] rounded-2xl p-4 lg:p-5 flex flex-col gap-4">
                    @foreach ($match['stats'] as $s)
                        <div class="flex items-center gap-3">
                            <span class="w-11 flex-none text-right font-mono text-sm font-bold">{{ $s['home'] }}{{ $s['suffix'] }}</span>
                            <div class="flex-1 flex flex-col gap-1.5">
                                <span class="text-center text-[11px] text-text-muted">{{ $s['label'] }}</span>
                                <div class="h-1.5 rounded-full bg-white/[0.08] overflow-hidden flex">
                                    <span class="h-full {{ $accent['bg'] }}" style="width:{{ $s['pct_home'] }}%"></span>
                                    <span class="h-full bg-white/20" style="width:{{ 100 - $s['pct_home'] }}%"></span>
                                </div>
                            </div>
                            <span class="w-11 flex-none text-left font-mono text-sm font-bold">{{ $s['away'] }}{{ $s['suffix'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <script>
        (function () {
            const tabs = document.querySelectorAll('[data-match-tab]');
            if (! tabs.length) return;

            function activate(name) {
                tabs.forEach((btn) => {
                    const isActive = btn.dataset.matchTab === name;
                    btn.classList.toggle('is-active-match-tab', isActive);
                    btn.classList.toggle('bg-white/[0.1]', isActive);
                    btn.classList.toggle('bg-surface', ! isActive);
                    btn.classList.toggle('border', ! isActive);
                    btn.classList.toggle('border-white/[0.08]', ! isActive);
                    btn.classList.toggle('text-text-muted', ! isActive);
                });
                document.querySelectorAll('[data-match-panel]').forEach((panel) => {
                    panel.classList.toggle('hidden', panel.dataset.matchPanel !== name);
                });
            }

            tabs.forEach((btn) => btn.addEventListener('click', () => activate(btn.dataset.matchTab)));

            const firstAvailable = document.querySelector('[data-match-panel]');
            activate(firstAvailable ? firstAvailable.dataset.matchPanel : 'tok');
        })();
    </script>
</x-layouts.app>
