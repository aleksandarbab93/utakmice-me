@props(['sport', 'accent'])

<nav class="lg:hidden fixed bottom-0 inset-x-0 h-16 border-t border-white/[0.07] bg-[#0A0C0F] flex items-center justify-around px-2 z-10">
    <button type="button" data-tab="sve" data-active-class="text-accent" data-inactive-class="text-text-dim" class="tab-btn is-active-tab flex flex-col items-center gap-1.5 text-accent">
        <x-icon name="scores" class="w-5 h-5" />
        <span class="text-[10px] font-semibold">Sve</span>
    </button>
    <button type="button" data-tab="uzivo" data-active-class="text-accent" data-inactive-class="text-text-dim" class="tab-btn flex flex-col items-center gap-1.5 text-text-dim">
        <span class="w-5 h-5 flex items-center justify-center">
            <span class="w-2 h-2 rounded-full bg-current"></span>
        </span>
        <span class="text-[10px] font-semibold">Uživo</span>
    </button>
    <button type="button" data-tab="favorizovani" data-active-class="text-accent" data-inactive-class="text-text-dim" class="tab-btn flex flex-col items-center gap-1.5 text-text-dim">
        <x-icon name="star" class="w-5 h-5" />
        <span class="text-[10px] font-semibold">Favorizovane</span>
    </button>
    <a href="{{ \App\Support\Nav::leagues() }}" class="flex flex-col items-center gap-1.5 text-text-dim">
        <x-icon name="standings" class="w-5 h-5" />
        <span class="text-[10px] font-semibold">Lige</span>
    </a>
</nav>
