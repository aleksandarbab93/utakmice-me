@props(['sport', 'accent'])

<footer class="border-t border-white/[0.07] bg-[#0A0C0F]">
    {{-- Desktop --}}
    <div class="hidden lg:block px-7 pt-9">
        <div class="grid gap-8 pb-8" style="grid-template-columns:1.4fr 1fr 1fr 1fr 1fr">
            <div class="flex flex-col gap-3.5">
                <div class="flex items-center gap-2.5">
                    <x-logo size="md" />
                </div>
                <p class="text-sm leading-relaxed text-text-muted max-w-[34ch]">Rezultati, tabele i vijesti iz liga petice i evropske košarke. Uživo, bez čekanja na osvežavanje strane.</p>
                <span class="w-9.5 h-9.5 rounded-full bg-surface border border-white/[0.08] flex items-center justify-center" style="width:38px;height:38px">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#C8CFD8" stroke-width="1.8"><rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1.1" fill="#C8CFD8" stroke="none"/></svg>
                </span>
            </div>

            <div class="flex flex-col gap-3">
                <span class="font-mono text-[9.5px] font-bold tracking-[0.16em] text-text-dim">FUDBAL</span>
                @foreach (\App\Support\Accent::leagues('fudbal') as $league)
                    <a href="{{ \App\Support\Nav::home('fudbal') }}" class="text-[13.5px] text-text-2">{{ $league }}</a>
                @endforeach
            </div>

            <div class="flex flex-col gap-3">
                <span class="font-mono text-[9.5px] font-bold tracking-[0.16em] text-text-dim">KOŠARKA</span>
                @foreach (\App\Support\Accent::leagues('kosarka') as $league)
                    <a href="{{ \App\Support\Nav::home('kosarka') }}" class="text-[13.5px] text-text-2">{{ $league }}</a>
                @endforeach
                <a href="{{ \App\Support\Nav::standings('kosarka') }}" class="text-[13.5px] text-text-2">Tabele takmičenja</a>
            </div>

            <div class="flex flex-col gap-3">
                <span class="font-mono text-[9.5px] font-bold tracking-[0.16em] text-text-dim">SEKCIJE</span>
                <a href="{{ \App\Support\Nav::scores($sport) }}" class="text-[13.5px] text-text-2">Utakmice</a>
                <a href="{{ \App\Support\Nav::standings($sport) }}" class="text-[13.5px] text-text-2">Tabele</a>
                <a href="{{ \App\Support\Nav::home($sport) }}" class="text-[13.5px] text-text-2">Vijesti</a>
                <a href="{{ route('streams') }}" class="text-[13.5px] text-text-2">Prenosi uživo</a>
                <span class="text-[13.5px] text-text-2">Statistika</span>
            </div>

            <div class="flex flex-col gap-3">
                <span class="font-mono text-[9.5px] font-bold tracking-[0.16em] text-text-dim">O SAJTU</span>
                <span class="text-[13.5px] text-text-2">O nama</span>
                <span class="text-[13.5px] text-text-2">Kontakt</span>
                <span class="text-[13.5px] text-text-2">Marketing</span>
                <span class="text-[13.5px] text-text-2">Uslovi korišćenja</span>
                <span class="text-[13.5px] text-text-2">Politika privatnosti</span>
            </div>
        </div>

        <div class="border-t border-white/[0.07] py-4.5 flex items-center justify-between gap-5">
            <span class="font-mono text-[10px] tracking-[0.1em] text-text-dim">© 2026 UTAKMICE.ME &middot; SVA PRAVA ZADRŽANA</span>
            <div class="flex items-center gap-5">
                <span class="font-mono text-[10px] tracking-[0.1em] text-text-dim">REZULTATI SE OSVEŽAVAJU SVAKIH 30 S</span>
                <span class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-positive"></span>
                    <span class="font-mono text-[10px] tracking-[0.1em] text-text-muted">SVI SERVISI RADE</span>
                </span>
            </div>
        </div>
    </div>

    {{-- Mobile --}}
    <div class="lg:hidden px-4 pt-6 pb-24 flex flex-col gap-5">
        <div class="flex flex-col gap-2.5">
            <div class="flex items-center gap-2">
                <x-logo />
            </div>
            <p class="text-[13.5px] leading-relaxed text-text-muted">Rezultati, tabele i vijesti iz liga petice i evropske košarke.</p>
        </div>

        <div class="grid grid-cols-2 gap-5">
            <div class="flex flex-col gap-2.5">
                <span class="font-mono text-[9px] font-bold tracking-[0.16em] text-text-dim">FUDBAL</span>
                @foreach (\App\Support\Accent::leagues('fudbal') as $league)
                    <a href="{{ \App\Support\Nav::home('fudbal') }}" class="text-[13px] text-text-2">{{ $league }}</a>
                @endforeach
            </div>
            <div class="flex flex-col gap-2.5">
                <span class="font-mono text-[9px] font-bold tracking-[0.16em] text-text-dim">KOŠARKA</span>
                @foreach (\App\Support\Accent::leagues('kosarka') as $league)
                    <a href="{{ \App\Support\Nav::home('kosarka') }}" class="text-[13px] text-text-2">{{ $league }}</a>
                @endforeach
                <a href="{{ \App\Support\Nav::standings('kosarka') }}" class="text-[13px] text-text-2">Tabele</a>
            </div>
        </div>

        <span class="w-11 h-11 rounded-full bg-surface border border-white/[0.08] flex items-center justify-center">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#C8CFD8" stroke-width="1.8"><rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1.1" fill="#C8CFD8" stroke="none"/></svg>
        </span>

        <div class="flex flex-col gap-2 pt-3 border-t border-white/[0.07]">
            <div class="flex gap-3.5 pt-1">
                <span class="text-[12.5px] text-text-muted">Uslovi</span>
                <span class="text-[12.5px] text-text-muted">Privatnost</span>
                <span class="text-[12.5px] text-text-muted">Kontakt</span>
            </div>
            <span class="font-mono text-[9.5px] tracking-[0.1em] text-text-dim">© 2026 UTAKMICE.ME &middot; OSVEŽAVANJE 30 S</span>
        </div>
    </div>
</footer>
