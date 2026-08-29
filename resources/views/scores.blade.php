<x-layouts.app :sport="$sport" :accent="$accent" :active="$active" :title="'Rezultati — Utakmice.me'">
    <div class="max-w-3xl mx-auto lg:py-6">
        <div class="px-4 lg:px-0 pt-3 pb-1 flex gap-2">
            <span class="flex-1 lg:flex-none lg:px-6 h-8.5 rounded-full flex items-center justify-center bg-white/[0.1] font-mono text-[10px] font-bold tracking-[0.1em]" style="height:34px">DANAS</span>
            <span class="flex-1 lg:flex-none lg:px-6 h-8.5 rounded-full flex items-center justify-center bg-surface border border-white/[0.08] text-text-muted font-mono text-[10px] tracking-[0.1em]" style="height:34px">JUČE</span>
            <span class="flex-1 lg:flex-none lg:px-6 h-8.5 rounded-full flex items-center justify-center bg-surface border border-white/[0.08] text-text-muted font-mono text-[10px] tracking-[0.1em]" style="height:34px">SUTRA</span>
        </div>

        @foreach ($grouped as $league => $leagueMatches)
            <div class="px-4 lg:px-0 pt-6 pb-2 flex items-center gap-2.5">
                <span class="w-[3px] h-3.5 rounded {{ $accent['bg'] }}"></span>
                <span class="font-mono text-[10px] font-bold tracking-[0.16em] text-text-2">{{ strtoupper($league) }}</span>
            </div>
            <div class="mx-4 lg:mx-0 bg-surface border border-white/[0.07] rounded-2xl overflow-hidden">
                @foreach ($leagueMatches as $match)
                    <div class="{{ !$loop->last ? 'border-b border-white/[0.05]' : '' }}">
                        <x-score-row :match="$match" />
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</x-layouts.app>
