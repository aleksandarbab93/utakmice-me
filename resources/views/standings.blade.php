<x-layouts.app :sport="$sport" :accent="$accent" :active="$active" :title="'Tabele — Utakmice.me'">
    <div class="max-w-2xl mx-auto lg:py-6">
        <div class="px-4 lg:px-0 pt-3 pb-1 flex gap-2 scrollrow">
            @foreach ($standings['competitions'] as $competition)
                <a href="{{ \App\Support\Nav::standings($sport, \Illuminate\Support\Str::slug($competition)) }}"
                   class="flex-none h-8 px-3.5 rounded-full flex items-center text-[12.5px] {{ $competition === $standings['competition'] ? 'bg-white/[0.1] font-semibold' : 'bg-surface border border-white/[0.08] text-text-2' }}">{{ $competition }}</a>
            @endforeach
        </div>

        <div class="mx-4 lg:mx-0 mt-4">
            <x-standings-table :rows="$standings['rows']" :accent="$accent" :zones="$standings['zones']" />
        </div>

        @if ($standings['next'])
            <div class="mx-4 lg:mx-0 mt-4 p-3.5 rounded-2xl border border-dashed border-white/[0.12] flex flex-col gap-1.5">
                <span class="font-mono text-[9px] tracking-[0.14em] text-text-dim">SLEDEĆE NA PROGRAMU</span>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold">{{ $standings['next']['label'] }}</span>
                    <span class="font-mono text-[11px] {{ $accent['text'] }}">{{ $standings['next']['when'] }}</span>
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
