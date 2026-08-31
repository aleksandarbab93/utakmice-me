@props(['games', 'title'])

<div class="flex flex-col gap-2">
    <span class="font-mono text-[9.5px] font-bold tracking-[0.14em] text-text-dim">{{ strtoupper($title) }}</span>
    <div class="bg-surface border border-white/[0.07] rounded-2xl overflow-hidden">
        @forelse ($games as $g)
            <div class="flex items-center gap-2.5 px-3.5 py-2.5 {{ !$loop->last ? 'border-b border-white/[0.05]' : '' }}">
                <span class="font-mono text-[9px] text-text-dim w-11 flex-none">{{ $g['date'] }}</span>
                <div class="flex-1 min-w-0 flex items-center gap-2">
                    <span class="text-[12.5px] flex-1 min-w-0 truncate text-right">{{ $g['home'] }}</span>
                    <span class="font-mono text-[12px] font-bold flex-none">{{ $g['home_score'] }}:{{ $g['away_score'] }}</span>
                    <span class="text-[12.5px] flex-1 min-w-0 truncate">{{ $g['away'] }}</span>
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
