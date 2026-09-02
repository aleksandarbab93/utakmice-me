@props(['sport', 'accent', 'active'])

@php
    $otherSport = $sport === 'kosarka' ? 'fudbal' : 'kosarka';
@endphp

<header class="border-b border-white/[0.07]">
    {{-- Mobile top bar --}}
    <div class="lg:hidden h-14 flex items-center justify-between px-4">
        @if ($active === 'home')
            <a href="{{ \App\Support\Nav::home($sport) }}" class="flex items-center gap-2">
                <x-logo />
            </a>
            <button class="w-9 h-9 rounded-full bg-surface border border-white/[0.08] flex items-center justify-center text-text-muted">
                <x-icon name="search" class="w-4 h-4" />
            </button>
        @elseif ($active === 'scores')
            <span class="text-xl font-extrabold tracking-tight">Utakmice</span>
            <x-mobile-nav-drawer :sport="$sport" />
        @elseif ($active === 'streams')
            <div class="flex items-center gap-3">
                <a href="{{ \App\Support\Nav::home($sport) }}" class="w-8 h-8 rounded-full bg-surface border border-white/[0.08] flex items-center justify-center text-text-muted">
                    <x-icon name="back" class="w-3.5 h-3.5" />
                </a>
                <span class="text-xl font-extrabold tracking-tight">Prenosi uživo</span>
            </div>
        @else
            <div class="flex items-center gap-3">
                <a href="{{ \App\Support\Nav::home($sport) }}" class="w-8 h-8 rounded-full bg-surface border border-white/[0.08] flex items-center justify-center text-text-muted">
                    <x-icon name="back" class="w-3.5 h-3.5" />
                </a>
                <span class="text-xl font-extrabold tracking-tight">Tabele</span>
            </div>
        @endif
    </div>

    @if (! in_array($active, ['standings', 'streams'], true))
        <div class="lg:hidden px-4 pb-3 flex gap-1.5">
            <a href="{{ $active === 'scores' ? \App\Support\Nav::scores('fudbal') : \App\Support\Nav::home('fudbal') }}"
               class="flex-1 h-10 rounded-[10px] flex items-center justify-center text-sm font-bold {{ $sport === 'fudbal' ? 'bg-accent-football text-bg' : 'bg-surface border border-white/[0.08] text-text-muted font-semibold' }}">
                Fudbal
            </a>
            <a href="{{ $active === 'scores' ? \App\Support\Nav::scores('kosarka') : \App\Support\Nav::home('kosarka') }}"
               class="flex-1 h-10 rounded-[10px] flex items-center justify-center text-sm font-bold {{ $sport === 'kosarka' ? 'bg-accent-basketball text-bg' : 'bg-surface border border-white/[0.08] text-text-muted font-semibold' }}">
                Košarka
            </a>
        </div>
    @endif

    {{-- Desktop bar --}}
    <div class="hidden lg:flex h-16 items-center justify-between px-7">
        <div class="flex items-center gap-7">
            <a href="{{ \App\Support\Nav::home($sport) }}" class="flex items-center gap-2.5">
                <x-logo size="md" />
            </a>
            <nav class="flex gap-5 text-[13.5px] text-text-2">
                <a href="{{ \App\Support\Nav::scores($sport) }}" class="{{ $active === 'scores' ? 'text-text font-semibold' : '' }}">Utakmice</a>
                <a href="{{ \App\Support\Nav::standings($sport) }}" class="{{ $active === 'standings' ? 'text-text font-semibold' : '' }}">Tabele</a>
                <a href="{{ \App\Support\Nav::home($sport) }}">Vijesti</a>
                <a href="{{ route('streams') }}" class="{{ $active === 'streams' ? 'text-text font-semibold' : '' }}">Prenosi uživo</a>
            </nav>
        </div>
        <div class="w-64 h-9 rounded-full bg-surface border border-white/[0.08] flex items-center gap-2 px-3.5 text-text-dim text-[13px]">
            <x-icon name="search" class="w-3.5 h-3.5" />
            <span>Pretraga klubova i vijesti</span>
        </div>
    </div>

    {{-- Sport switcher row --}}
    @if ($active !== 'streams')
    <div class="hidden lg:flex items-center px-7 py-3 border-t border-white/[0.07]">
        <div class="flex gap-1 bg-surface border border-white/[0.08] rounded-[10px] p-1">
            <a href="{{ $active === 'scores' ? \App\Support\Nav::scores('fudbal') : ($active === 'standings' ? \App\Support\Nav::standings('fudbal') : \App\Support\Nav::home('fudbal')) }}"
               class="px-4.5 py-1.5 rounded-[7px] text-[13.5px] {{ $sport === 'fudbal' ? 'bg-accent-football text-bg font-bold' : 'text-text-muted font-semibold' }}">
                Fudbal
            </a>
            <a href="{{ $active === 'scores' ? \App\Support\Nav::scores('kosarka') : ($active === 'standings' ? \App\Support\Nav::standings('kosarka') : \App\Support\Nav::home('kosarka')) }}"
               class="px-4.5 py-1.5 rounded-[7px] text-[13.5px] {{ $sport === 'kosarka' ? 'bg-accent-basketball text-bg font-bold' : 'text-text-muted font-semibold' }}">
                Košarka
            </a>
        </div>
    </div>
    @endif
</header>
