@props(['paginator'])

@php
    $current = $paginator->currentPage();
    $total = $paginator->lastPage();

    if ($total <= 8) {
        $pages = range(1, $total);
    } elseif ($current <= 4) {
        $pages = [1, 2, 3, 4, 5, 6, '…', $total - 1, $total];
    } elseif ($current >= $total - 3) {
        $pages = [1, 2, '…', $total - 4, $total - 3, $total - 2, $total - 1, $total];
    } else {
        $pages = [1, '…', $current - 1, $current, $current + 1, '…', $total - 1, $total];
    }

    $mobileWindow = $current <= 2
        ? [1, 2, 3]
        : ($current >= $total - 1 ? [$total - 2, $total - 1, $total] : [$current - 1, $current, $current + 1]);
    $mobileWindow = array_values(array_unique(array_filter($mobileWindow, fn ($p) => $p >= 1 && $p <= $total)));
@endphp

@if ($total > 1)
    <nav aria-label="Paginacija" class="flex justify-center">
        {{-- Desktop --}}
        <div class="hidden lg:flex items-center gap-2">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" class="w-10 h-10 flex items-center justify-center rounded-[10px] bg-surface border border-white/[0.08] text-[#3A424D] text-[15px]">‹</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="w-10 h-10 flex items-center justify-center rounded-[10px] bg-surface border border-white/[0.08] text-text-2 text-[15px]">‹</a>
            @endif

            @foreach ($pages as $p)
                @if ($p === '…')
                    <span class="min-w-10 h-10 flex items-center justify-center text-[#4A5462] font-mono text-[13px]">…</span>
                @elseif ($p === $current)
                    <span aria-current="page" class="min-w-10 h-10 px-1.5 flex items-center justify-center rounded-[10px] bg-accent text-[#0A0C0F] font-mono text-[13px] font-bold">{{ $p }}</span>
                @else
                    <a href="{{ $paginator->url($p) }}" class="min-w-10 h-10 px-1.5 flex items-center justify-center rounded-[10px] bg-surface border border-white/[0.08] text-text-2 font-mono text-[13px] transition-colors duration-[120ms] hover:bg-accent/14 hover:border-accent/45 hover:text-accent hover:font-semibold">{{ $p }}</a>
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="w-10 h-10 flex items-center justify-center rounded-[10px] bg-surface border border-white/[0.08] text-text-2 text-[15px]">›</a>
            @else
                <span aria-disabled="true" class="w-10 h-10 flex items-center justify-center rounded-[10px] bg-surface border border-white/[0.08] text-[#3A424D] text-[15px]">›</span>
            @endif
        </div>

        {{-- Mobile --}}
        <div class="lg:hidden w-full bg-surface-2 border border-white/[0.07] rounded-2xl p-3.5 flex items-center justify-between gap-2.5">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" class="w-11 h-11 flex-none flex items-center justify-center rounded-xl bg-surface border border-white/[0.08] text-[#3A424D] text-base">‹</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="w-11 h-11 flex-none flex items-center justify-center rounded-xl bg-surface border border-white/[0.08] text-text-2 text-base">‹</a>
            @endif

            <div class="flex items-center gap-1.5">
                @foreach ($mobileWindow as $p)
                    @if ($p === $current)
                        <span aria-current="page" class="min-w-11 h-11 px-1 flex items-center justify-center rounded-xl bg-accent text-[#0A0C0F] font-mono text-[13.5px] font-bold">{{ $p }}</span>
                    @else
                        <a href="{{ $paginator->url($p) }}" class="min-w-11 h-11 px-1 flex items-center justify-center rounded-xl bg-surface border border-white/[0.08] text-text-2 font-mono text-[13.5px]">{{ $p }}</a>
                    @endif
                @endforeach
            </div>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="w-11 h-11 flex-none flex items-center justify-center rounded-xl bg-surface border border-white/[0.08] text-text-2 text-base">›</a>
            @else
                <span aria-disabled="true" class="w-11 h-11 flex-none flex items-center justify-center rounded-xl bg-surface border border-white/[0.08] text-[#3A424D] text-base">›</span>
            @endif
        </div>
    </nav>
@endif
