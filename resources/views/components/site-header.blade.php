@props(['sport', 'accent', 'active'])

@php
    $otherSport = $sport === 'kosarka' ? 'fudbal' : 'kosarka';
@endphp

<header class="border-b border-white/[0.07]">
    {{-- Mobile top bar: logo + hamburger, always — the page's own heading lives in its content, not here. --}}
    <div class="lg:hidden h-14 flex items-center justify-between px-4">
        <a href="{{ \App\Support\Nav::home($sport) }}" class="flex items-center gap-2">
            <x-logo />
        </a>
        <div class="flex items-center gap-2">
            @if ($active === 'home')
                <button class="w-8 h-8 rounded-full bg-surface border border-white/[0.08] flex items-center justify-center text-text-muted">
                    <x-icon name="search" class="w-3.5 h-3.5" />
                </button>
            @endif
            <x-mobile-nav-drawer :sport="$sport" />
        </div>
    </div>

    @if (! in_array($active, ['standings', 'streams', 'leagues', 'league'], true))
        <div class="lg:hidden px-4 pb-3 flex gap-1.5">
            <a href="{{ $active === 'scores' ? \App\Support\Nav::scores('fudbal') : \App\Support\Nav::home('fudbal') }}"
               class="flex-1 h-10 rounded-[10px] flex items-center justify-center text-sm font-bold {{ $sport === 'fudbal' ? 'bg-accent text-bg' : 'bg-surface border border-white/[0.08] text-text-muted font-semibold' }}">
                Fudbal
            </a>
            <a href="{{ $active === 'scores' ? \App\Support\Nav::scores('kosarka') : \App\Support\Nav::home('kosarka') }}"
               class="flex-1 h-10 rounded-[10px] flex items-center justify-center text-sm font-bold {{ $sport === 'kosarka' ? 'bg-accent text-bg' : 'bg-surface border border-white/[0.08] text-text-muted font-semibold' }}">
                Košarka
            </a>
        </div>
    @endif

    {{-- Desktop bar --}}
    <div class="hidden lg:flex justify-center h-16">
        <div class="flex-1 max-w-[1120px] flex items-center justify-between px-7">
            <div class="flex items-center gap-7">
                <a href="{{ \App\Support\Nav::home($sport) }}" class="flex items-center gap-2.5">
                    <x-logo size="md" />
                </a>
                <nav class="flex gap-5 text-[13.5px] text-text-2">
                    <a href="{{ \App\Support\Nav::scores($sport) }}" class="{{ $active === 'scores' ? 'text-accent font-semibold' : '' }}">Utakmice</a>
                    <a href="{{ \App\Support\Nav::news() }}" class="{{ $active === 'vijesti' ? 'text-accent font-semibold' : '' }}">Vijesti</a>
                    <a href="{{ route('streams') }}" class="flex items-center gap-1.5 {{ $active === 'streams' ? 'text-accent font-semibold' : '' }}">
                        <span class="w-1.5 h-1.5 rounded-full bg-live animate-live"></span>
                        Prenos uživo
                    </a>
                    <a href="{{ \App\Support\Nav::leagues() }}" class="{{ in_array($active, ['leagues', 'league', 'standings'], true) ? 'text-accent font-semibold' : '' }}">Lige</a>
                </nav>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-64 h-9 rounded-full bg-surface border border-white/[0.08] flex items-center gap-2 px-3.5 text-text-dim text-[13px]">
                    <x-icon name="search" class="w-3.5 h-3.5" />
                    <span>Pretraga klubova i vijesti</span>
                </div>
                <button type="button" hidden data-push-toggle aria-pressed="false"
                        class="w-9 h-9 rounded-full bg-surface border border-white/[0.08] flex items-center justify-center text-text-dim flex-none">
                    <x-icon name="bell" class="w-4 h-4" />
                </button>
            </div>
        </div>
    </div>

    {{-- Sport switcher row --}}
    @if (! in_array($active, ['streams', 'leagues', 'league'], true))
    <div class="hidden lg:flex justify-center border-t border-white/[0.07]">
        <div class="flex-1 max-w-[1120px] flex items-center px-7 py-3">
            <div class="flex gap-1 bg-surface border border-white/[0.08] rounded-[10px] p-1">
                <a href="{{ $active === 'scores' ? \App\Support\Nav::scores('fudbal') : ($active === 'standings' ? \App\Support\Nav::standings('fudbal') : \App\Support\Nav::home('fudbal')) }}"
                   class="px-4.5 py-1.5 rounded-[7px] text-[13.5px] {{ $sport === 'fudbal' ? 'bg-accent text-bg font-bold' : 'text-text-muted font-semibold' }}">
                    Fudbal
                </a>
                <a href="{{ $active === 'scores' ? \App\Support\Nav::scores('kosarka') : ($active === 'standings' ? \App\Support\Nav::standings('kosarka') : \App\Support\Nav::home('kosarka')) }}"
                   class="px-4.5 py-1.5 rounded-[7px] text-[13.5px] {{ $sport === 'kosarka' ? 'bg-accent text-bg font-bold' : 'text-text-muted font-semibold' }}">
                    Košarka
                </a>
            </div>
        </div>
    </div>
    @endif
</header>
