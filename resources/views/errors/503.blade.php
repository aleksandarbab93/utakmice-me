{{-- Shown when the database is momentarily unreachable, with Retry-After
     set, so a crawler comes back instead of dropping the page. Deliberately
     free of anything that would need the database to render. --}}
<x-layouts.minimal title="Trenutno nedostupno — Utakmice.me">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-[420px] flex flex-col gap-4 items-start">
            <div class="flex items-center gap-2">
                <span class="w-[3px] h-4 rounded-full bg-accent"></span>
                <span class="font-mono text-[10px] font-bold tracking-[0.16em] text-text-dim">UTAKMICE.ME</span>
            </div>

            <h1 class="text-[26px] font-extrabold leading-tight tracking-tight">Vraćamo se za koji minut</h1>

            <p class="text-[15px] leading-relaxed text-text-muted">
                Server je trenutno preopterećen i ne može da učita rezultate.
                Osvježi stranicu za minut-dva — podaci nisu izgubljeni.
            </p>

            <a href="/" class="h-11 px-5 rounded-full bg-text text-bg text-sm font-bold flex items-center">Pokušaj ponovo</a>
        </div>
    </div>
</x-layouts.minimal>
