<x-layouts.app :sport="$sport" :accent="$accent" :active="$active" :title="$league->name.' — Utakmice.me'" :description="'Tabela, rezultati i raspored '.$league->name.' — uživo, ažurirano poslije svake odigrane utakmice.'">
    <div class="max-w-[1120px] mx-auto px-4 lg:px-7 py-5 lg:py-7 flex flex-col gap-4">
        <x-league-hero :league="$league" :club-count="$clubCount" :season="$season" :round="$round" />

        <x-league-tabs :league="$league" :accent="$accent" active="standings" />

        <div class="flex flex-col gap-4">
            @if (empty($standings['rows']))
                <div class="border border-dashed border-white/[0.12] rounded-2xl p-6 text-center text-text-muted text-sm">
                    Tabela još nije formirana — takmičenje je u fazi kvalifikacija.
                </div>
            @else
                <x-standings-table :rows="$standings['rows']" :accent="$accent" :zones="$standings['zones']" />
            @endif

            @if ($scorers->isNotEmpty())
                <div class="bg-surface border border-white/[0.07] rounded-2xl overflow-hidden">
                    <div class="px-3.5 py-3 border-b border-white/[0.07] font-mono text-[10px] font-bold tracking-[0.14em] text-text-2">NAJBOLJI STRIJELCI</div>
                    <table class="w-full font-mono text-[13px]">
                        <thead>
                            <tr class="border-b border-white/[0.07] text-[9px] tracking-[0.1em] text-text-dim">
                                <th class="w-9 px-3.5 py-2.5 text-left font-normal">#</th>
                                <th class="px-1 py-2.5 text-left font-normal">IGRAČ</th>
                                <th class="w-14 px-1 py-2.5 text-right font-normal">GOLOVA</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($scorers as $i => $row)
                                <tr class="{{ !$loop->last ? 'border-b border-white/[0.05]' : '' }}">
                                    <td class="px-3.5 py-2.5 text-xs text-text-dim">{{ $i + 1 }}</td>
                                    <td class="px-1 py-2.5 font-sans">
                                        <span class="block text-[13.5px] font-semibold">{{ $row->player_name }}</span>
                                        @if ($row->team)
                                            <span class="block text-[10.5px] text-text-dim">{{ $row->team->name }}</span>
                                        @endif
                                    </td>
                                    <td class="px-1 py-2.5 text-right text-[13.5px] font-bold">{{ $row->goals }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($results->isNotEmpty())
                <div class="bg-surface border border-white/[0.07] rounded-2xl overflow-hidden">
                    <div class="px-3.5 py-3 border-b border-white/[0.07] font-mono text-[10px] font-bold tracking-[0.14em] text-text-2">{{ strtoupper($round ?? 'REZULTATI KOLA') }}</div>
                    @foreach ($results as $match)
                        <x-score-row :match="$match" />
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
