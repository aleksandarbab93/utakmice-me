<x-layouts.app :sport="$sport" :accent="$accent" :active="$active" :title="'Rezultati — Utakmice.me'">
    <div class="max-w-[1440px] mx-auto lg:px-7 lg:py-6">
        <div class="lg:grid lg:gap-7" style="grid-template-columns: 220px 1fr">

            {{-- Sidebar (desktop only) --}}
            <aside class="hidden lg:block">
                <div class="font-mono text-[10px] font-bold tracking-[0.16em] text-text-dim mb-3">LIGE</div>
                <div class="flex flex-col gap-1">
                    @foreach ($leagues as $league)
                        <div class="flex items-center justify-between gap-2 px-2 py-1.5 rounded-lg hover:bg-surface">
                            <a href="#liga-{{ $league['slug'] }}" class="flex items-center gap-2 text-[13.5px] text-text-2 min-w-0">
                                <x-league-icon :icon="\App\Support\Accent::leagueIcon($league['name'])" class="w-4 h-3" />
                                <span class="truncate">{{ $league['name'] }}</span>
                            </a>
                            <a href="{{ \App\Support\Nav::standings($sport, $league['slug']) }}" class="font-mono text-[9.5px] tracking-[0.05em] text-text-dim flex-none">Tabela</a>
                        </div>
                    @endforeach
                </div>
            </aside>

            <div class="px-4 lg:px-0 pt-3 lg:pt-0">
                {{-- Tabs + date nav --}}
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <div class="flex gap-2" id="score-tabs">
                        <button data-tab="sve" class="tab-btn is-active-tab h-8.5 px-4 rounded-full flex items-center font-mono text-[10.5px] font-bold tracking-[0.1em] bg-white/[0.1]" style="height:34px">SVE</button>
                        <button data-tab="uzivo" class="tab-btn h-8.5 px-4 rounded-full flex items-center font-mono text-[10.5px] font-bold tracking-[0.1em] bg-surface border border-white/[0.08] text-text-muted" style="height:34px">UŽIVO</button>
                        <button data-tab="favorizovani" class="tab-btn h-8.5 px-4 rounded-full flex items-center font-mono text-[10.5px] font-bold tracking-[0.1em] bg-surface border border-white/[0.08] text-text-muted" style="height:34px">FAVORIZOVANI</button>
                    </div>
                    <div class="flex items-center justify-between gap-3 w-full lg:w-auto lg:justify-start lg:gap-2">
                        <a href="{{ \App\Support\Nav::scores($sport, $prevDate) }}" class="w-8.5 h-8.5 rounded-full bg-surface border border-white/[0.08] flex items-center justify-center text-text-muted flex-none" style="width:34px;height:34px">
                            <x-icon name="back" class="w-3.5 h-3.5" />
                        </a>
                        <span class="font-mono text-[12px] font-bold tracking-[0.08em] px-2 min-w-[90px] text-center">{{ $dateLabel }}</span>
                        <a href="{{ \App\Support\Nav::scores($sport, $nextDate) }}" class="w-8.5 h-8.5 rounded-full bg-surface border border-white/[0.08] flex items-center justify-center text-text-muted rotate-180 flex-none" style="width:34px;height:34px">
                            <x-icon name="back" class="w-3.5 h-3.5" />
                        </a>
                    </div>
                </div>

                {{-- League groups --}}
                <div class="mt-5 flex flex-col gap-6" id="score-groups">
                    @forelse ($groups as $group)
                        <div data-league-group id="liga-{{ $group['slug'] }}" class="scroll-mt-20">
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <button data-collapse-toggle class="flex items-center gap-2.5 min-w-0">
                                    <span class="w-[3px] h-3.5 rounded {{ $accent['bg'] }} flex-none"></span>
                                    <x-league-icon :icon="$group['flag']" class="w-4 h-3" />
                                    <span class="font-mono text-[10px] font-bold tracking-[0.16em] text-text-2 truncate">{{ strtoupper($group['name']) }}</span>
                                    <svg data-chevron width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="text-text-dim transition-transform"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <a href="{{ \App\Support\Nav::standings($sport, $group['slug']) }}" class="font-mono text-[9.5px] tracking-[0.05em] text-text-dim flex-none">Tabela &rsaquo;</a>
                            </div>
                            <div data-league-body class="bg-surface border border-white/[0.07] rounded-2xl overflow-hidden">
                                @foreach ($group['matches'] as $m)
                                    @php
                                        $homeWins = $m['status'] === 'finished' && $m['home_score'] > $m['away_score'];
                                        $awayWins = $m['status'] === 'finished' && $m['away_score'] > $m['home_score'];
                                    @endphp
                                    @php
                                        $scoreColor = $m['status'] === 'live' ? 'text-live-text' : '';
                                    @endphp
                                    <a href="{{ \App\Support\Nav::match($m['id']) }}" data-match-row data-match-id="{{ $m['id'] }}" data-status="{{ $m['status'] }}" class="flex items-center gap-3 px-3.5 py-3 border-b border-white/[0.05] last:border-0 hover:bg-white/[0.02]">
                                        <button data-fav-star="{{ $m['id'] }}" class="flex-none w-5 h-5 flex items-center justify-center text-text-dim" aria-label="Favorizuj">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><path d="M12 3.5l2.6 5.6 6.1.7-4.5 4.2 1.2 6-5.4-3-5.4 3 1.2-6-4.5-4.2 6.1-.7z"/></svg>
                                        </button>

                                        {{-- Desktop: status column on the left --}}
                                        <div class="hidden lg:block w-11 flex-none text-center">
                                            @if ($m['status'] === 'live')
                                                <span class="font-mono text-[10px] font-bold text-live-text">{{ $m['minute'] }}</span>
                                            @elseif ($m['status'] === 'scheduled')
                                                <span class="font-mono text-[11px] text-text-muted">{{ $m['kickoff'] }}</span>
                                            @else
                                                <span class="font-mono text-[9px] tracking-[0.1em] text-text-dim">KRAJ</span>
                                            @endif
                                        </div>

                                        <div class="flex-1 min-w-0 flex flex-col gap-1.5">
                                            <span class="flex items-center gap-2 min-w-0 {{ $awayWins ? 'text-text-muted' : '' }}">
                                                <x-team-badge :initials="$m['homeInitials']" :crest="$m['homeCrest'] ?? null" class="w-6 h-6 text-[9px]" />
                                                <span class="text-sm truncate {{ $awayWins ? '' : 'font-semibold' }}">{{ $m['home'] }}</span>
                                            </span>
                                            <span class="flex items-center gap-2 min-w-0 {{ $homeWins ? 'text-text-muted' : '' }}">
                                                <x-team-badge :initials="$m['awayInitials']" :crest="$m['awayCrest'] ?? null" class="w-6 h-6 text-[9px]" />
                                                <span class="text-sm truncate {{ $homeWins ? '' : 'font-semibold' }}">{{ $m['away'] }}</span>
                                            </span>
                                        </div>

                                        {{-- Mobile: status column between names and scores, vertically centered --}}
                                        <div class="lg:hidden flex-none w-9 text-center self-center">
                                            @if ($m['status'] === 'live')
                                                <span class="font-mono text-[10px] font-bold text-live-text">{{ $m['minute'] }}</span>
                                            @elseif ($m['status'] === 'scheduled')
                                                <span class="font-mono text-[10.5px] text-text-muted">{{ $m['kickoff'] }}</span>
                                            @else
                                                <span class="font-mono text-[9px] tracking-[0.1em] text-text-dim">KRAJ</span>
                                            @endif
                                        </div>

                                        @if ($m['status'] !== 'scheduled')
                                            <div class="flex-none flex flex-col gap-1.5 items-end">
                                                <span class="font-mono text-sm font-bold {{ $scoreColor }} {{ $awayWins ? 'text-text-muted' : '' }}">{{ $m['home_score'] }}</span>
                                                <span class="font-mono text-sm font-bold {{ $scoreColor }} {{ $homeWins ? 'text-text-muted' : '' }}">{{ $m['away_score'] }}</span>
                                            </div>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="border border-dashed border-white/[0.12] rounded-2xl p-6 text-center text-text-muted text-sm">
                            Nema mečeva za {{ strtolower($dateLabel) }}.
                        </div>
                    @endforelse
                </div>

                <div id="score-empty-tab" class="hidden border border-dashed border-white/[0.12] rounded-2xl p-6 text-center text-text-muted text-sm mt-5">
                    Nema mečeva u ovom prikazu.
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const FAV_KEY = 'utakmice_favorites';
            const getFavs = () => { try { return JSON.parse(localStorage.getItem(FAV_KEY) || '[]'); } catch (e) { return []; } };
            const setFavs = (arr) => { try { localStorage.setItem(FAV_KEY, JSON.stringify(arr)); } catch (e) {} };

            function paintStar(btn, isFav) {
                const svg = btn.querySelector('svg');
                svg.setAttribute('fill', isFav ? 'currentColor' : 'none');
                btn.classList.toggle('text-accent', isFav);
                btn.classList.toggle('text-text-dim', !isFav);
            }

            const favs = new Set(getFavs());
            document.querySelectorAll('[data-fav-star]').forEach((btn) => {
                const id = btn.dataset.favStar;
                paintStar(btn, favs.has(id));
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    if (favs.has(id)) { favs.delete(id); } else { favs.add(id); }
                    setFavs([...favs]);
                    paintStar(btn, favs.has(id));
                    applyFilter();
                });
            });

            let activeTab = 'sve';
            function applyFilter() {
                let anyVisible = false;
                document.querySelectorAll('[data-league-group]').forEach((group) => {
                    let groupHasVisible = false;
                    group.querySelectorAll('[data-match-row]').forEach((row) => {
                        const isLive = row.dataset.status === 'live';
                        const isFav = favs.has(row.dataset.matchId);
                        let show = true;
                        if (activeTab === 'uzivo') show = isLive;
                        if (activeTab === 'favorizovani') show = isFav;
                        row.style.display = show ? '' : 'none';
                        if (show) groupHasVisible = true;
                    });
                    group.style.display = groupHasVisible ? '' : 'none';
                    if (groupHasVisible) anyVisible = true;
                });
                document.getElementById('score-empty-tab').classList.toggle('hidden', anyVisible);
            }

            document.querySelectorAll('.tab-btn').forEach((btn) => {
                btn.addEventListener('click', () => {
                    activeTab = btn.dataset.tab;
                    document.querySelectorAll('.tab-btn').forEach((b) => {
                        b.classList.remove('is-active-tab', 'bg-white/[0.1]');
                        b.classList.add('bg-surface', 'border', 'border-white/[0.08]', 'text-text-muted');
                    });
                    btn.classList.add('is-active-tab', 'bg-white/[0.1]');
                    btn.classList.remove('bg-surface', 'border', 'border-white/[0.08]', 'text-text-muted');
                    applyFilter();
                });
            });

            document.querySelectorAll('[data-collapse-toggle]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const group = btn.closest('[data-league-group]');
                    const body = group.querySelector('[data-league-body]');
                    const chevron = btn.querySelector('[data-chevron]');
                    body.classList.toggle('hidden');
                    chevron.style.transform = body.classList.contains('hidden') ? 'rotate(-90deg)' : '';
                });
            });

            applyFilter();
        })();
    </script>
</x-layouts.app>
