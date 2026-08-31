@props(['games', 'title'])

<div class="flex flex-col gap-2">
    <span class="font-mono text-[9.5px] font-bold tracking-[0.14em] text-text-dim">{{ strtoupper($title) }}</span>
    <div class="bg-surface border border-white/[0.07] rounded-2xl overflow-hidden">
        @forelse ($games as $g)
            @php
                $finished = $g['home_score'] !== null && $g['away_score'] !== null;
                $homeWins = $finished && $g['home_score'] > $g['away_score'];
                $awayWins = $finished && $g['away_score'] > $g['home_score'];
            @endphp
            <div class="flex items-center gap-2.5 px-3.5 py-2.5 {{ !$loop->last ? 'border-b border-white/[0.05]' : '' }}">
                <span class="font-mono text-[9px] text-text-dim w-11 flex-none">{{ $g['date'] }}</span>
                <div class="flex-1 min-w-0 flex flex-col gap-1.5">
                    <div class="flex items-center gap-1.5 min-w-0 {{ $awayWins ? 'text-text-muted' : '' }}">
                        <x-team-badge :initials="\App\Support\TeamBadge::initials($g['home'])" :crest="$g['home_crest'] ?? null" class="w-4.5 h-4.5 text-[7px]" />
                        <span class="text-[12.5px] truncate {{ $awayWins ? '' : 'font-semibold' }}">{{ $g['home'] }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 min-w-0 {{ $homeWins ? 'text-text-muted' : '' }}">
                        <x-team-badge :initials="\App\Support\TeamBadge::initials($g['away'])" :crest="$g['away_crest'] ?? null" class="w-4.5 h-4.5 text-[7px]" />
                        <span class="text-[12.5px] truncate {{ $homeWins ? '' : 'font-semibold' }}">{{ $g['away'] }}</span>
                    </div>
                </div>
                <div class="flex flex-col items-end flex-none gap-1.5">
                    <span class="font-mono text-[13px] font-bold {{ $awayWins ? 'text-text-muted' : '' }}">{{ $g['home_score'] }}</span>
                    <span class="font-mono text-[13px] font-bold {{ $homeWins ? 'text-text-muted' : '' }}">{{ $g['away_score'] }}</span>
                </div>
                @if ($g['result'])
                    <span class="flex-none w-5 h-5 rounded flex items-center justify-center font-mono text-[10px] font-bold {{ $g['result'] === 'W' ? 'bg-positive/20 text-positive' : ($g['result'] === 'L' ? 'bg-negative/20 text-negative' : 'bg-white/10 text-text-muted') }}">{{ $g['result'] }}</span>
                @endif
            </div>
        @empty
            <div class="px-3.5 py-3 text-center text-[12px] text-text-dim">Nema podataka.</div>
        @endforelse
    </div>
</div>
