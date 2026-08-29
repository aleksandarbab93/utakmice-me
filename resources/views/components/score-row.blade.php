@props(['match'])

@php
    $homeWins = $match['status'] === 'finished' && $match['home_score'] > $match['away_score'];
    $awayWins = $match['status'] === 'finished' && $match['away_score'] > $match['home_score'];
@endphp

<div class="flex items-center gap-3.5 px-3.5 py-3">
    <div class="w-11 flex-none flex flex-col items-center gap-1 text-center">
        @if ($match['status'] === 'live')
            <span class="w-1.5 h-1.5 rounded-full bg-live animate-live"></span>
            <span class="font-mono text-[9px] font-bold text-live-text">{{ $match['minute'] ?? $match['period'] ?? '' }}</span>
        @elseif ($match['status'] === 'scheduled')
            <span class="font-mono text-[11px] text-text-muted">{{ $match['kickoff'] }}</span>
        @else
            <span class="font-mono text-[9px] tracking-[0.1em] text-text-dim">KRAJ</span>
        @endif
    </div>
    <div class="flex-1 flex flex-col gap-1.5">
        <div class="flex justify-between {{ $awayWins ? 'text-text-muted' : '' }}">
            <span class="text-sm {{ $awayWins ? '' : 'font-semibold' }}">{{ $match['home'] }}</span>
            <span class="font-mono text-sm font-bold {{ $match['status'] === 'scheduled' ? 'text-text-dim' : '' }}">
                {{ $match['status'] === 'scheduled' ? '–' : $match['home_score'] }}
            </span>
        </div>
        <div class="flex justify-between {{ $homeWins ? 'text-text-muted' : '' }}">
            <span class="text-sm {{ $homeWins ? '' : 'font-semibold' }}">{{ $match['away'] }}</span>
            <span class="font-mono text-sm font-bold {{ $match['status'] === 'scheduled' ? 'text-text-dim' : '' }}">
                {{ $match['status'] === 'scheduled' ? '–' : $match['away_score'] }}
            </span>
        </div>
    </div>
</div>
