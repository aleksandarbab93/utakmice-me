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
        <table class="w-full min-w-[600px] border-collapse font-mono table-fixed">
            <colgroup>
                <col style="width:34px"><col><col style="width:34px"><col style="width:30px"><col style="width:30px"><col style="width:30px"><col style="width:52px"><col style="width:44px"><col style="width:38px"><col style="width:96px">
            </colgroup>
            <thead>
                <tr class="border-b border-white/[0.07] text-[9px] tracking-[0.1em] text-text-dim">
                    <th class="px-2 py-2.5 text-left font-normal">#</th>
                    <th class="px-1 py-2.5 text-left font-normal">KLUB</th>
                    <th class="px-1 py-2.5 text-center font-normal">OS</th>
                    <th class="px-1 py-2.5 text-center font-normal">P</th>
                    <th class="px-1 py-2.5 text-center font-normal">N</th>
                    <th class="px-1 py-2.5 text-center font-normal">I</th>
                    <th class="px-1 py-2.5 text-center font-normal">G</th>
                    <th class="px-1 py-2.5 text-center font-normal">GR</th>
                    <th class="px-1 py-2.5 text-center font-normal">B</th>
                    <th class="px-2 py-2.5 text-right font-normal">FORMA</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr class="{{ !$loop->last ? 'border-b border-white/[0.05]' : '' }} {{ in_array($row['team'], $highlight, true) ? 'bg-white/[0.04]' : '' }}">
                        <td class="px-2 py-3">
                            <span class="flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full flex-none {{ ! empty($row['zone']) ? $zoneDot($row['zone']) : '' }}"></span>
                                <span class="text-xs font-bold {{ $row['pos'] === 1 ? $accent['text'] : 'text-text-muted' }}">{{ $row['pos'] }}</span>
                            </span>
                        </td>
                        <td class="px-1 py-3 font-sans text-[13.5px] {{ $row['pos'] === 1 ? 'font-bold' : 'font-semibold' }} truncate max-w-0">{{ $row['team'] }}</td>
                        <td class="px-1 py-3 text-xs text-text-2 text-center">{{ $row['played'] }}</td>
                        <td class="px-1 py-3 text-xs text-text-2 text-center">{{ $row['won'] ?? '—' }}</td>
                        <td class="px-1 py-3 text-xs text-text-2 text-center">{{ $row['draw'] ?? '—' }}</td>
                        <td class="px-1 py-3 text-xs text-text-2 text-center">{{ $row['lost'] ?? '—' }}</td>
                        <td class="px-1 py-3 text-xs text-text-2 text-center whitespace-nowrap">{{ isset($row['goals_for']) ? $row['goals_for'].':'.$row['goals_against'] : '—' }}</td>
                        <td class="px-1 py-3 text-xs text-center {{ str_starts_with($row['diff'], '+') ? 'text-positive' : (str_starts_with($row['diff'], '-') ? 'text-negative' : 'text-text-muted') }}">{{ $row['diff'] }}</td>
                        <td class="px-1 py-3 text-xs font-bold text-center">{{ $row['points'] }}</td>
                        <td class="px-2 py-3">
                            <span class="flex items-center justify-end gap-1">
                                @forelse (($row['form'] ?? []) as $r)
                                    <span class="w-4 h-4 rounded-[3px] flex items-center justify-center text-[8.5px] font-bold {{ $r === 'W' ? 'bg-positive/20 text-positive' : ($r === 'L' ? 'bg-negative/20 text-negative' : 'bg-white/10 text-text-muted') }}">{{ $r }}</span>
                                @empty
                                    <span class="text-text-dim text-[10px]">—</span>
                                @endforelse
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
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
