@props(['rows', 'accent', 'highlight' => [], 'zones' => null])

@php
    $zoneDot = fn ($zone) => match ($zone) {
        'cl' => 'bg-sky-500',
        'el' => 'bg-rose-800',
        'relegation' => 'bg-negative',
        default => 'bg-white/15',
    };
    $hasZones = $zones && (count($zones['cl']) || count($zones['el']) || $zones['relegationCount'] > 0);
@endphp

<div class="bg-surface border border-white/[0.07] rounded-2xl overflow-hidden">
    <div class="overflow-x-auto">
        <div class="min-w-[560px]">
            <div class="grid gap-2 px-3.5 py-2.5 border-b border-white/[0.07] font-mono text-[9px] tracking-[0.1em] text-text-dim" style="grid-template-columns:22px 1fr 28px 28px 28px 28px 48px 40px 36px 100px">
                <span>#</span><span>KLUB</span><span class="text-center">OS</span><span class="text-center">P</span><span class="text-center">N</span><span class="text-center">I</span><span class="text-center">G</span><span class="text-center">GR</span><span class="text-center">B</span><span class="text-right">FORMA</span>
            </div>
            @foreach ($rows as $row)
                <div class="grid gap-2 px-3.5 py-3 items-center {{ !$loop->last ? 'border-b border-white/[0.05]' : '' }} {{ in_array($row['team'], $highlight, true) ? 'bg-white/[0.04]' : '' }}">
                    <span class="flex items-center gap-1.5" style="grid-column:1">
                        @if (! empty($row['zone']))
                            <span class="w-1.5 h-1.5 rounded-full flex-none {{ $zoneDot($row['zone']) }}"></span>
                        @endif
                        <span class="font-mono text-xs font-bold {{ $row['pos'] === 1 ? $accent['text'] : 'text-text-muted' }}">{{ $row['pos'] }}</span>
                    </span>
                    <span class="text-[13.5px] truncate {{ $row['pos'] === 1 ? 'font-bold' : 'font-semibold' }}" style="grid-column:2">{{ $row['team'] }}</span>
                    <span class="font-mono text-xs text-text-2 text-center" style="grid-column:3">{{ $row['played'] }}</span>
                    <span class="font-mono text-xs text-text-2 text-center" style="grid-column:4">{{ $row['won'] ?? '—' }}</span>
                    <span class="font-mono text-xs text-text-2 text-center" style="grid-column:5">{{ $row['draw'] ?? '—' }}</span>
                    <span class="font-mono text-xs text-text-2 text-center" style="grid-column:6">{{ $row['lost'] ?? '—' }}</span>
                    <span class="font-mono text-xs text-text-2 text-center" style="grid-column:7">{{ isset($row['goals_for']) ? $row['goals_for'].':'.$row['goals_against'] : '—' }}</span>
                    <span class="font-mono text-xs text-center {{ str_starts_with($row['diff'], '+') ? 'text-positive' : (str_starts_with($row['diff'], '-') ? 'text-negative' : 'text-text-muted') }}" style="grid-column:8">{{ $row['diff'] }}</span>
                    <span class="font-mono text-xs font-bold text-center" style="grid-column:9">{{ $row['points'] }}</span>
                    <span class="flex items-center justify-end gap-1" style="grid-column:10">
                        @forelse (($row['form'] ?? []) as $r)
                            <span class="w-4 h-4 rounded-[3px] flex items-center justify-center font-mono text-[8.5px] font-bold {{ $r === 'W' ? 'bg-positive/20 text-positive' : ($r === 'L' ? 'bg-negative/20 text-negative' : 'bg-white/10 text-text-muted') }}">{{ $r }}</span>
                        @empty
                            <span class="text-text-dim text-[10px]">—</span>
                        @endforelse
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    @if ($hasZones)
        <div class="px-3.5 py-2.5 border-t border-white/[0.07] flex flex-col gap-1.5 font-mono text-[9px] text-text-dim">
            @if (count($zones['cl']))
                <span class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>Kvalifikacija — Liga prvaka</span>
            @endif
            @if (count($zones['el']))
                <span class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-rose-800"></span>Kvalifikacija — Evropska liga</span>
            @endif
            @if ($zones['relegationCount'] > 0)
                <span class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-negative"></span>Ispadanje</span>
            @endif
        </div>
    @endif
</div>
