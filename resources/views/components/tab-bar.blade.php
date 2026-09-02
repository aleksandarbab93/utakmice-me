@props(['sport', 'accent', 'active'])

<nav class="lg:hidden fixed bottom-0 inset-x-0 h-16 border-t border-white/[0.07] bg-[#0A0C0F] flex items-center justify-around px-2 z-10">
    <a href="{{ \App\Support\Nav::home($sport) }}" class="flex flex-col items-center gap-1.5 {{ $active === 'home' ? $accent['text'] : 'text-text-dim' }}">
        <x-icon name="home" class="w-5 h-5" />
        <span class="text-[10px] {{ $active === 'home' ? 'font-bold' : 'font-semibold' }}">Naslovna</span>
    </a>
    <a href="{{ \App\Support\Nav::scores($sport) }}" class="flex flex-col items-center gap-1.5 {{ $active === 'scores' ? $accent['text'] : 'text-text-dim' }}">
        <x-icon name="scores" class="w-5 h-5" />
        <span class="text-[10px] {{ $active === 'scores' ? 'font-bold' : 'font-semibold' }}">Utakmice</span>
    </a>
    <a href="{{ \App\Support\Nav::standings($sport) }}" class="flex flex-col items-center gap-1.5 {{ $active === 'standings' ? $accent['text'] : 'text-text-dim' }}">
        <x-icon name="standings" class="w-5 h-5" />
        <span class="text-[10px] {{ $active === 'standings' ? 'font-bold' : 'font-semibold' }}">Lige</span>
    </a>
    <a href="{{ \App\Support\Nav::news() }}" class="flex flex-col items-center gap-1.5 {{ $active === 'vijesti' ? $accent['text'] : 'text-text-dim' }}">
        <x-icon name="news" class="w-5 h-5" />
        <span class="text-[10px] {{ $active === 'vijesti' ? 'font-bold' : 'font-semibold' }}">Vijesti</span>
    </a>
</nav>
