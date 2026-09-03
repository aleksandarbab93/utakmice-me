@props(['sport'])

<button id="mobile-menu-btn" type="button" class="w-8 h-8 rounded-full bg-surface border border-white/[0.08] flex items-center justify-center text-text-muted" aria-label="Meni">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
</button>

<div id="mobile-menu-backdrop" class="hidden fixed inset-0 bg-black/60 z-40"></div>

<nav id="mobile-menu-drawer" class="fixed inset-y-0 right-0 w-[70%] max-w-xs bg-bg border-l border-white/[0.08] z-50 translate-x-full transition-transform duration-200 flex flex-col">
    <div class="h-14 flex items-center justify-between px-4 border-b border-white/[0.07]">
        <x-logo />
        <button id="mobile-menu-close" type="button" class="w-8 h-8 rounded-full bg-surface border border-white/[0.08] flex items-center justify-center text-text-muted" aria-label="Zatvori">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>
    <div class="flex flex-col p-2 gap-0.5">
        <a href="{{ \App\Support\Nav::home($sport) }}" class="px-3.5 py-3 rounded-lg text-[15px] font-semibold text-text">Naslovna</a>
        <a href="{{ \App\Support\Nav::scores($sport) }}" class="px-3.5 py-3 rounded-lg text-[15px] font-semibold text-text">Utakmice</a>
        <a href="{{ \App\Support\Nav::news() }}" class="px-3.5 py-3 rounded-lg text-[15px] font-semibold text-text">Vijesti</a>
        <a href="{{ route('streams') }}" class="px-3.5 py-3 rounded-lg text-[15px] font-semibold text-text flex items-center gap-2">
            <span class="w-1.5 h-1.5 rounded-full bg-live animate-live"></span>
            Prenos uživo
        </a>
        <a href="{{ \App\Support\Nav::leagues() }}" class="px-3.5 py-3 rounded-lg text-[15px] font-semibold text-text">Lige</a>

        <button type="button" hidden data-push-toggle
                class="mt-1 px-3.5 py-3 rounded-lg text-[15px] font-semibold text-text-dim flex items-center gap-2.5 border-t border-white/[0.07]">
            <x-icon name="bell" class="w-4 h-4" />
            <span data-push-state>Obavještenja o golovima</span>
        </button>
    </div>
</nav>

<script>
    (function () {
        const btn = document.getElementById('mobile-menu-btn');
        const backdrop = document.getElementById('mobile-menu-backdrop');
        const drawer = document.getElementById('mobile-menu-drawer');
        const closeBtn = document.getElementById('mobile-menu-close');
        if (! btn || ! backdrop || ! drawer || ! closeBtn) return;

        function open() {
            backdrop.classList.remove('hidden');
            drawer.classList.remove('translate-x-full');
        }

        function close() {
            backdrop.classList.add('hidden');
            drawer.classList.add('translate-x-full');
        }

        btn.addEventListener('click', open);
        closeBtn.addEventListener('click', close);
        backdrop.addEventListener('click', close);
    })();
</script>
