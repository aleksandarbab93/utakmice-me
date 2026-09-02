<x-layouts.app :sport="$sport" :accent="$accent" :active="$active" :title="$match['home']['name'].' - '.$match['away']['name'].' — Utakmice.me'">
    <div class="max-w-[720px] mx-auto px-4 lg:px-0 py-5 lg:py-8 flex flex-col gap-5">

        {{-- Breadcrumb --}}
        <div class="flex items-center gap-2 font-mono text-[10px] tracking-[0.12em] text-text-dim">
            <a href="{{ \App\Support\Nav::home('kosarka') }}" class="{{ $accent['text'] }}">KOŠARKA</a>
            <span>&rsaquo;</span>
            <x-league-icon :icon="$match['flag']" class="w-4 h-3" />
            <span>{{ strtoupper($match['league']) }}</span>
            @if ($match['round'])
                <span>&rsaquo;</span>
                <span>{{ strtoupper($match['round']) }}</span>
            @endif
        </div>

        {{-- Score header --}}
        <div class="bg-surface border border-white/[0.07] rounded-2xl p-5 lg:p-7 flex flex-col gap-5">
            <div class="grid items-center gap-3" style="grid-template-columns:1fr auto 1fr">
                <div class="flex flex-col items-center gap-2.5 text-center">
                    <x-team-badge :initials="$match['home']['initials']" :crest="$match['home']['crest'] ?? null" class="w-14 h-14 text-base" />
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
                    <x-team-badge :initials="$match['away']['initials']" :crest="$match['away']['crest'] ?? null" class="w-14 h-14 text-base" />
                    <span class="text-sm lg:text-[15px] font-bold">{{ $match['away']['name'] }}</span>
                </div>
            </div>

            @if ($match['venue'])
                <div class="pt-4 border-t border-white/[0.07] flex flex-wrap justify-center gap-x-4 gap-y-1 font-mono text-[10px] tracking-[0.08em] text-text-dim">
                    <span>{{ strtoupper($match['venue']) }}</span>
                </div>
            @endif
        </div>

        @if (empty($match['preview']) && empty($match['standings']['rows']))
            <div class="border border-dashed border-white/[0.12] rounded-2xl p-6 text-center text-text-muted text-sm">
                Detalji meča trenutno nisu dostupni.
            </div>
        @else
            <div class="flex gap-2" id="match-tabs">
                @if (! empty($match['preview']))
                    <button data-match-tab="pregled" class="match-tab-btn h-8.5 px-4 rounded-full flex items-center font-mono text-[10.5px] font-bold tracking-[0.1em] bg-surface border border-white/[0.08] text-text-muted" style="height:34px">PREGLED</button>
                @endif
                @if (! empty($match['standings']['rows']))
                    <button data-match-tab="tablica" class="match-tab-btn h-8.5 px-4 rounded-full flex items-center font-mono text-[10.5px] font-bold tracking-[0.1em] bg-surface border border-white/[0.08] text-text-muted" style="height:34px">TABLICA</button>
                @endif
            </div>
        @endif

        {{-- Pregled (H2H + forma) --}}
        @if (! empty($match['preview']))
            <div class="hidden flex-col gap-4" data-match-panel="pregled">
                <x-match-form-list :games="$match['preview']['h2h']" title="Posljednji međusobni duel" />
                <x-match-form-list :games="$match['preview']['home_form']" :title="'Posljednji mečevi: '.$match['home']['name']" />
                <x-match-form-list :games="$match['preview']['away_form']" :title="'Posljednji mečevi: '.$match['away']['name']" />
            </div>
        @endif

        {{-- Tablica --}}
        @if (! empty($match['standings']['rows']))
            <div class="hidden" data-match-panel="tablica">
                <x-standings-table :rows="$match['standings']['rows']" :zones="$match['standings']['zones']" :accent="$accent" :highlight="[$match['home']['name'], $match['away']['name']]" />
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
            activate(firstAvailable ? firstAvailable.dataset.matchPanel : 'pregled');
        })();
    </script>
</x-layouts.app>
