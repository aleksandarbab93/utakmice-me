@props(['match'])

@php
    $homeWins = $match['status'] === 'finished' && $match['home_score'] > $match['away_score'];
    $awayWins = $match['status'] === 'finished' && $match['away_score'] > $match['home_score'];
    $scoreColor = $match['status'] === 'live' ? 'text-live-text' : '';
@endphp

<a href="{{ \App\Support\Nav::match($match['id']) }}" class="flex items-center gap-3 px-3.5 py-3 border-b border-white/[0.05] last:border-0 hover:bg-white/[0.02]">
    <div class="hidden lg:block w-11 flex-none text-center">
        @if ($match['status'] === 'live')
            <span class="font-mono text-[10px] font-bold text-live-text">{{ $match['minute'] }}</span>
        @elseif ($match['status'] === 'scheduled')
            <span class="font-mono text-[11px] text-text-muted">{{ $match['kickoff'] }}</span>
        @else
            <span class="font-mono text-[9px] tracking-[0.1em] text-text-dim">KRAJ</span>
        @endif
    </div>

    <div class="flex-1 min-w-0 flex flex-col gap-1.5">
        <span class="flex items-center gap-2 min-w-0 {{ $awayWins ? 'text-text-muted' : '' }}">
            <x-team-badge :initials="$match['homeInitials']" :crest="$match['homeCrest'] ?? null" class="w-6 h-6 text-[9px]" />
            <span class="text-sm truncate {{ $awayWins ? '' : 'font-semibold' }}">{{ $match['home'] }}</span>
        </span>
        <span class="flex items-center gap-2 min-w-0 {{ $homeWins ? 'text-text-muted' : '' }}">
            <x-team-badge :initials="$match['awayInitials']" :crest="$match['awayCrest'] ?? null" class="w-6 h-6 text-[9px]" />
            <span class="text-sm truncate {{ $homeWins ? '' : 'font-semibold' }}">{{ $match['away'] }}</span>
        </span>
    </div>

    <div class="lg:hidden flex-none w-9 text-center self-center">
        @if ($match['status'] === 'live')
            <span class="font-mono text-[10px] font-bold text-live-text">{{ $match['minute'] }}</span>
        @elseif ($match['status'] === 'scheduled')
            <span class="font-mono text-[10.5px] text-text-muted">{{ $match['kickoff'] }}</span>
        @else
            <span class="font-mono text-[9px] tracking-[0.1em] text-text-dim">KRAJ</span>
        @endif
    </div>

    @if ($match['status'] !== 'scheduled')
        <div class="flex-none flex flex-col gap-1.5 items-end">
            <span class="font-mono text-sm font-bold {{ $scoreColor }} {{ $awayWins ? 'text-text-muted' : '' }}">{{ $match['home_score'] }}</span>
            <span class="font-mono text-sm font-bold {{ $scoreColor }} {{ $homeWins ? 'text-text-muted' : '' }}">{{ $match['away_score'] }}</span>
        </div>
    @endif
</a>
