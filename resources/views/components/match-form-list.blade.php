@props(['games', 'title'])

<div class="flex flex-col gap-2">
    <span class="font-mono text-[9px] lg:text-[10px] font-bold tracking-[0.14em] text-text-muted">{{ strtoupper($title) }}</span>
    <div class="bg-surface border border-white/[0.07] rounded-2xl overflow-hidden">
        @forelse ($games as $g)
            @php
                $finished = $g['home_score'] !== null && $g['away_score'] !== null;
                $homeWins = $finished && $g['home_score'] > $g['away_score'];
                $awayWins = $finished && $g['away_score'] > $g['home_score'];
            @endphp
            <div class="flex items-center gap-2.5 px-3.5 py-2 lg:py-3 {{ !$loop->last ? 'border-b border-white/[0.05]' : '' }}">
                {{-- Day and month only: these are all from the last few weeks,
                     and the year was costing the club names the room they need
                     to not be cut off on a phone. --}}
                <span class="font-mono text-[10px] text-text-dim w-9 flex-none">{{ \Illuminate\Support\Str::beforeLast($g['date'], '.') }}</span>
                <div class="flex-1 min-w-0 flex flex-col gap-1 lg:gap-1.5">
                    <div class="flex items-center gap-1.5 min-w-0 {{ $awayWins ? 'text-text-muted' : '' }}">
                        <x-team-badge :initials="\App\Support\TeamBadge::initials($g['home'])" :crest="$g['home_crest'] ?? null" class="w-4 h-4 lg:w-4.5 lg:h-4.5 text-[7px]" />
                        <span class="text-[12.5px] lg:text-[13px] truncate {{ $awayWins ? '' : 'font-semibold' }}">{{ $g['home'] }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 min-w-0 {{ $homeWins ? 'text-text-muted' : '' }}">
                        <x-team-badge :initials="\App\Support\TeamBadge::initials($g['away'])" :crest="$g['away_crest'] ?? null" class="w-4 h-4 lg:w-4.5 lg:h-4.5 text-[7px]" />
                        <span class="text-[12.5px] lg:text-[13px] truncate {{ $homeWins ? '' : 'font-semibold' }}">{{ $g['away'] }}</span>
                    </div>
                </div>
                <div class="flex flex-col items-end flex-none gap-2">
                    <span class="font-mono text-[13px] lg:text-[13.5px] font-bold {{ $awayWins ? 'text-text-muted' : '' }}">{{ $g['home_score'] }}</span>
                    <span class="font-mono text-[13px] lg:text-[13.5px] font-bold {{ $homeWins ? 'text-text-muted' : '' }}">{{ $g['away_score'] }}</span>
                </div>
                @if ($g['result'])
                    <span class="flex-none w-5 h-5 rounded flex items-center justify-center font-mono text-[10px] font-bold {{ $g['result'] === 'W' ? 'bg-positive/20 text-positive' : ($g['result'] === 'L' ? 'bg-negative/20 text-negative' : 'bg-white/10 text-text-muted') }}">{{ $g['result'] }}</span>
                @endif
            </div>
        @empty
            <div class="px-3.5 py-5 text-center text-[12.5px] text-text-dim">Nema podataka.</div>
        @endforelse
    </div>
</div>
