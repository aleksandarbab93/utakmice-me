@props(['league', 'clubCount', 'season' => null, 'round' => null])

<div class="flex items-center gap-3.5">
    <span class="w-12 h-12 rounded-2xl bg-surface border border-white/[0.07] flex-none overflow-hidden flex items-center justify-center">
        <x-league-icon :icon="\App\Support\Accent::leagueIcon($league->name)" class="w-8 h-6" />
    </span>
    <div class="flex flex-col gap-0.5 min-w-0">
        <h1 class="text-xl lg:text-2xl font-extrabold tracking-tight truncate">{{ $league->name }}</h1>
        <p class="font-mono text-[11px] tracking-[0.05em] text-text-dim">
            @if ($season)Sezona {{ $season }} &middot; @endif{{ $clubCount }} {{ \App\Support\Plural::sr($clubCount, 'klub', 'kluba', 'klubova') }}@if ($round) &middot; {{ $round }}@endif
        </p>
    </div>
</div>
