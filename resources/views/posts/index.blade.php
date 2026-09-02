@php
    $toggleSortUrl = route('post.index', array_filter([
        'liga' => $activeLeague,
        'sort' => $oldestFirst ? null : 'najstarije',
    ]));
@endphp

<x-layouts.app :sport="$sport" :accent="$accent" :active="$active" :title="'Vijesti — Utakmice.me'" :description="$description" :canonical="$canonical">
    <div class="max-w-[1120px] mx-auto px-4 lg:px-7 py-5 lg:py-7 flex flex-col gap-5">

        {{-- Page head --}}
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-3">
            <div class="flex items-start gap-2.5 lg:gap-3">
                <span class="flex-none w-1 h-6 lg:h-8 rounded-[2px] {{ $accent['bg'] }} mt-0.5"></span>
                <div class="flex flex-col gap-1.5 lg:gap-2">
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-2xl lg:text-[32px] font-extrabold tracking-tight leading-none">Vijesti</h1>
                        <span class="lg:hidden font-mono text-[9.5px] tracking-[0.1em] text-text-dim">{{ $posts->total() }} TEKSTOVA</span>
                    </div>
                    <p class="hidden lg:block text-[15px] leading-relaxed text-text-muted max-w-[62ch]">{{ $description }}</p>
                    <p class="lg:hidden text-[13.5px] leading-relaxed text-text-muted">Najnovije iz fudbala — lige petice i Liga šampiona. Rezultati i izvještaji, svakog dana.</p>
                    @if ($updatedLabel)
                        <span class="hidden lg:inline font-mono text-[10px] tracking-[0.12em] text-text-dim">{{ $posts->total() }} TEKSTOVA &middot; AŽURIRANO {{ strtoupper($updatedLabel) }}</span>
                    @endif
                </div>
            </div>

            <a href="{{ $toggleSortUrl }}"
               class="self-start lg:self-auto h-9 px-3.5 flex items-center gap-2 rounded-[10px] bg-surface border border-white/[0.08] font-mono text-[10.5px] tracking-[0.1em] text-text-2">
                {{ $oldestFirst ? 'NAJSTARIJE PRVO' : 'NAJNOVIJE PRVO' }}
                <span class="text-text-dim">&#9662;</span>
            </a>
        </div>

        {{-- League filter chips --}}
        <div class="flex items-center gap-2 flex-nowrap lg:flex-wrap overflow-x-auto pb-1 lg:pb-3 lg:border-b border-white/[0.07] scrollbar-none">
            <a href="{{ \App\Support\Nav::news() }}" class="flex-none">
                <span class="{{ $activeLeague === null ? 'bg-accent/14 border border-accent/45 text-accent font-semibold' : 'bg-surface border border-white/[0.08] text-text-2' }} h-8.5 px-3.5 rounded-full flex items-center text-[12.5px]" style="height:34px">Sve vijesti</span>
            </a>
            @foreach ($leagues as $league)
                <a href="{{ \App\Support\Nav::news($league['slug']) }}" class="flex-none">
                    <span class="{{ $activeLeague === $league['slug'] ? 'bg-accent/14 border border-accent/45 text-accent font-semibold' : 'bg-surface border border-white/[0.08] text-text-2' }} h-8.5 px-3.5 rounded-full flex items-center text-[12.5px]" style="height:34px">{{ $league['name'] }}</span>
                </a>
            @endforeach
        </div>

        @if ($posts->isEmpty() && ! $featured)
            <div class="py-10 flex flex-col items-center gap-2 text-center">
                <p class="text-[14.5px] text-text-muted">Nema vijesti za izabranu ligu.</p>
                <a href="{{ \App\Support\Nav::news() }}" class="font-mono text-[11px] tracking-[0.1em] text-accent">PRIKAŽI SVE VIJESTI</a>
            </div>
        @else
            @if ($featured)
                <div class="flex items-center gap-3">
                    <span class="font-mono text-[10px] font-bold tracking-[0.18em] text-text-2">{{ $featuredLabel }}</span>
                    <span class="flex-1 h-px bg-white/[0.07]"></span>
                </div>

                <a href="{{ route('post.show', $featured['slug']) }}" class="flex bg-surface border border-accent/22 rounded-2xl overflow-hidden">
                    <span class="flex-none w-1 bg-accent"></span>
                    <div class="px-4 py-4 lg:px-[30px] lg:py-[26px] flex flex-col gap-2.5 lg:gap-3 justify-center">
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-[9px] lg:text-[9.5px] font-bold tracking-[0.12em] px-2 py-1 rounded bg-accent/16 text-accent">{{ strtoupper($featured['league']) }}</span>
                            <span class="hidden lg:inline font-mono text-[9.5px] font-bold tracking-[0.12em] px-2 py-1 rounded bg-white/[0.06] text-text-muted">IZDVOJENO</span>
                        </div>
                        <h2 class="text-lg lg:text-[30px] font-extrabold leading-tight tracking-tight text-balance">{{ $featured['title'] }}</h2>
                        <p class="hidden lg:block text-[15px] leading-relaxed text-text-muted max-w-[52ch]">{{ $featured['lead'] }}</p>
                        <time datetime="{{ $featured['published_at']->toIso8601String() }}" class="font-mono text-[9.5px] tracking-[0.1em] text-text-dim">{{ strtoupper($featured['meta']) }} &middot; {{ $featured['read_minutes'] }} MIN ČITANJA</time>
                    </div>
                </a>
            @endif

            @foreach ($groups as $group)
                <div class="flex items-center gap-3 {{ !$loop->first || $featured ? 'mt-1' : '' }}">
                    <span class="font-mono text-[10px] font-bold tracking-[0.18em] text-text-2">{{ $group['label'] }}</span>
                    <span class="flex-1 h-px bg-white/[0.07]"></span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    @foreach ($group['posts'] as $post)
                        <a href="{{ route('post.show', $post['slug']) }}" class="bg-surface border border-white/[0.07] rounded-2xl px-4 py-3.5 lg:px-5 lg:py-[18px] flex flex-col gap-2.5 transition-colors duration-[120ms] hover:bg-[#171B21] group">
                            <span class="self-start font-mono text-[9px] font-bold tracking-[0.12em] px-2 py-1 rounded bg-accent/16 text-accent">{{ strtoupper($post['league']) }}</span>
                            <span class="text-[17px] font-bold leading-snug tracking-tight text-balance group-hover:text-accent">{{ $post['title'] }}</span>
                            <time datetime="{{ $post['published_at']->toIso8601String() }}" class="font-mono text-[9.5px] tracking-[0.1em] text-text-dim">{{ strtoupper($post['meta']) }}</time>
                        </a>
                    @endforeach
                </div>
            @endforeach

            <div class="pt-3 pb-1">
                <x-pagination :paginator="$posts" />
            </div>
        @endif
    </div>
</x-layouts.app>
