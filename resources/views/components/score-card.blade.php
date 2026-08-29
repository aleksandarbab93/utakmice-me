@props(['match'])

@php
    $homeWins = $match['status'] === 'finished' && $match['home_score'] > $match['away_score'];
    $awayWins = $match['status'] === 'finished' && $match['away_score'] > $match['home_score'];
@endphp

<div class="flex-none w-38 bg-surface border border-white/[0.07] rounded-2xl p-3 flex flex-col gap-2.5" style="width:152px">
    <div class="flex items-center justify-between">
        <span class="font-mono text-[9px] tracking-[0.12em] text-text-muted uppercase">{{ $match['league'] }}</span>
        @if ($match['status'] === 'live')
            <span class="flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-live animate-live"></span>
                <span class="font-mono text-[9px] font-bold text-live-text">{{ $match['minute'] ?? $match['period'] ?? '' }}</span>
            </span>
        @elseif ($match['status'] === 'scheduled')
            <span class="font-mono text-[9px] text-text-muted">{{ $match['kickoff'] }}</span>
        @else
            <span class="font-mono text-[9px] text-text-muted">KRAJ</span>
        @endif
    </div>
    <div class="flex justify-between {{ $awayWins ? 'text-text-muted' : '' }}">
        <span class="text-[13.5px] {{ $awayWins ? '' : 'font-semibold' }}">{{ $match['home'] }}</span>
        <span class="font-mono text-sm font-bold {{ $match['status'] === 'scheduled' ? 'text-text-dim' : '' }}">{{ $match['status'] === 'scheduled' ? '–' : $match['home_score'] }}</span>
    </div>
    <div class="flex justify-between {{ $homeWins ? 'text-text-muted' : '' }}">
        <span class="text-[13.5px] {{ $homeWins ? '' : 'font-semibold' }}">{{ $match['away'] }}</span>
        <span class="font-mono text-sm font-bold {{ $match['status'] === 'scheduled' ? 'text-text-dim' : '' }}">{{ $match['status'] === 'scheduled' ? '–' : $match['away_score'] }}</span>
    </div>
</div>
