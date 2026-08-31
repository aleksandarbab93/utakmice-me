@props(['games', 'title', 'accent' => null])

<div class="flex flex-col gap-2">
    <span class="font-mono text-[9.5px] font-bold tracking-[0.14em] text-text-dim">{{ strtoupper($title) }}</span>
    <div class="bg-surface border border-white/[0.07] rounded-2xl overflow-hidden">
        @forelse ($games as $g)
            <div class="flex items-center gap-2.5 px-3.5 py-2.5 {{ !$loop->last ? 'border-b border-white/[0.05]' : '' }}">
                <span class="font-mono text-[9px] text-text-dim w-11 flex-none">{{ $g['date'] }}</span>
                <div class="flex-1 min-w-0 flex flex-col gap-1.5">
                    <div class="flex items-center gap-1.5 min-w-0">
                        <x-team-badge :initials="\App\Support\TeamBadge::initials($g['home'])" :crest="$g['home_crest'] ?? null" class="w-4.5 h-4.5 text-[7px]" />
                        <span class="text-[12.5px] truncate {{ !empty($g['home_highlight']) ? 'font-bold '.($accent['text'] ?? 'text-text') : 'text-text-2' }}">{{ $g['home'] }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 min-w-0">
                        <x-team-badge :initials="\App\Support\TeamBadge::initials($g['away'])" :crest="$g['away_crest'] ?? null" class="w-4.5 h-4.5 text-[7px]" />
                        <span class="text-[12.5px] truncate {{ !empty($g['away_highlight']) ? 'font-bold '.($accent['text'] ?? 'text-text') : 'text-text-2' }}">{{ $g['away'] }}</span>
                    </div>
                </div>
                <span class="font-mono text-[13px] font-bold flex-none w-11 text-right">{{ $g['home_score'] }}:{{ $g['away_score'] }}</span>
                @if ($g['result'])
                    <span class="flex-none w-5 h-5 rounded flex items-center justify-center font-mono text-[10px] font-bold {{ $g['result'] === 'W' ? 'bg-positive/20 text-positive' : ($g['result'] === 'L' ? 'bg-negative/20 text-negative' : 'bg-white/10 text-text-muted') }}">{{ $g['result'] }}</span>
                @endif
            </div>
        @empty
            <div class="px-3.5 py-3 text-center text-[12px] text-text-dim">Nema podataka.</div>
        @endforelse
    </div>
</div>
