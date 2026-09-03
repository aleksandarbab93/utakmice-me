@props(['rows', 'accent', 'highlight' => [], 'zones' => null])

@php
    $zoneDot = fn ($zone) => match ($zone) {
        'cl' => 'bg-sky-500',
        'el' => 'bg-rose-800',
        'relegation' => 'bg-negative',
        default => 'bg-white/15',
    };
    $hasZones = $zones && (count($zones['cl']) || count($zones['el']) || $zones['relegationCount'] > 0);
    $resultClasses = fn ($result) => match ($result) {
        'W' => 'bg-positive/20 text-positive',
        'L' => 'bg-negative/20 text-negative',
        default => 'bg-yellow-400/20 text-yellow-400',
    };
@endphp

<div class="bg-surface border border-white/[0.07] rounded-2xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full lg:min-w-[600px] border-collapse font-mono table-fixed">
            <colgroup>
                <col style="width:34px">
                <col>
                <col style="width:34px">
                <col class="hidden lg:table-column" style="width:30px">
                <col class="hidden lg:table-column" style="width:30px">
                <col class="hidden lg:table-column" style="width:30px">
                <col style="width:52px">
                <col class="hidden lg:table-column" style="width:44px">
                <col style="width:38px">
                <col class="hidden lg:table-column" style="width:128px">
            </colgroup>
            <thead>
                <tr class="border-b border-white/[0.07] text-[9px] tracking-[0.1em] text-text-dim">
                    <th class="px-2 py-2.5 text-left font-normal">#</th>
                    <th class="px-1 py-2.5 text-left font-normal">KLUB</th>
                    <th class="px-1 py-2.5 text-center font-normal">OS</th>
                    <th class="hidden lg:table-cell px-1 py-2.5 text-center font-normal">P</th>
                    <th class="hidden lg:table-cell px-1 py-2.5 text-center font-normal">N</th>
                    <th class="hidden lg:table-cell px-1 py-2.5 text-center font-normal">I</th>
                    <th class="px-1 py-2.5 text-center font-normal">G</th>
                    <th class="hidden lg:table-cell px-1 py-2.5 text-center font-normal">GR</th>
                    <th class="px-1 py-2.5 text-center font-normal">B</th>
                    <th class="hidden lg:table-cell px-2 py-2.5 text-right font-normal">FORMA</th>
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
                        <td class="hidden lg:table-cell px-1 py-3 text-xs text-text-2 text-center">{{ $row['won'] ?? '—' }}</td>
                        <td class="hidden lg:table-cell px-1 py-3 text-xs text-text-2 text-center">{{ $row['draw'] ?? '—' }}</td>
                        <td class="hidden lg:table-cell px-1 py-3 text-xs text-text-2 text-center">{{ $row['lost'] ?? '—' }}</td>
                        <td class="px-1 py-3 text-xs text-text-2 text-center whitespace-nowrap">{{ isset($row['goals_for']) ? $row['goals_for'].':'.$row['goals_against'] : '—' }}</td>
                        <td class="hidden lg:table-cell px-1 py-3 text-xs text-center {{ str_starts_with($row['diff'], '+') ? 'text-positive' : (str_starts_with($row['diff'], '-') ? 'text-negative' : 'text-text-muted') }}">{{ $row['diff'] }}</td>
                        <td class="px-1 py-3 text-xs font-bold text-center">{{ $row['points'] }}</td>
                        <td class="hidden lg:table-cell px-2 py-3">
                            <span class="flex items-center justify-end gap-1">
                                @if (array_key_exists('next', $row))
                                    <button type="button"
                                            data-form-tip="{{ $row['next'] ?? 'Nema zakazanog meča.' }}"
                                            class="w-4 h-4 rounded-[3px] flex items-center justify-center text-[8.5px] font-bold bg-white/10 text-text-dim">?</button>
                                @endif
                                @forelse (($row['form'] ?? []) as $f)
                                    <button type="button"
                                            data-form-tip="{{ $f['tooltip'] }}"
                                            class="w-4 h-4 rounded-[3px] flex items-center justify-center text-[8.5px] font-bold {{ $resultClasses($f['result']) }}">{{ $f['result'] }}</button>
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

@once
    <script>
        (function () {
            let tip = null;
            let activeTarget = null;

            function ensureTip() {
                if (tip) return tip;
                tip = document.createElement('div');
                tip.id = 'form-tooltip';
                tip.className = 'fixed z-50 hidden px-3 py-2 rounded-lg bg-[#0A0C0F] border border-white/10 text-[11.5px] leading-relaxed text-text whitespace-pre-line pointer-events-none';
                tip.style.maxWidth = '220px';
                document.body.appendChild(tip);
                return tip;
            }

            function show(target) {
                const text = target.getAttribute('data-form-tip');
                if (!text) return;

                const el = ensureTip();
                el.textContent = text;
                el.classList.remove('hidden');

                const rect = target.getBoundingClientRect();
                const tipRect = el.getBoundingClientRect();
                let left = rect.left + rect.width / 2 - tipRect.width / 2;
                left = Math.max(8, Math.min(left, window.innerWidth - tipRect.width - 8));

                let top = rect.top - tipRect.height - 8;
                if (top < 8) top = rect.bottom + 8;

                el.style.left = left + 'px';
                el.style.top = top + 'px';
                activeTarget = target;
            }

            function hide() {
                if (tip) tip.classList.add('hidden');
                activeTarget = null;
            }

            document.addEventListener('mouseover', (e) => {
                const target = e.target.closest('[data-form-tip]');
                if (target) show(target);
            });

            document.addEventListener('mouseout', (e) => {
                if (e.target.closest('[data-form-tip]')) hide();
            });

            document.addEventListener('focusin', (e) => {
                const target = e.target.closest('[data-form-tip]');
                if (target) show(target);
            });

            document.addEventListener('focusout', (e) => {
                if (e.target.closest('[data-form-tip]')) hide();
            });

            // Touch devices: tap to toggle, tap elsewhere to close.
            document.addEventListener('click', (e) => {
                const target = e.target.closest('[data-form-tip]');
                if (target) {
                    e.preventDefault();
                    if (activeTarget === target && tip && !tip.classList.contains('hidden')) {
                        hide();
                    } else {
                        show(target);
                    }
                    return;
                }
                hide();
            });
        })();
    </script>
@endonce
