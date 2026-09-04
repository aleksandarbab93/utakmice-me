<x-layouts.app :sport="$sport" :accent="$accent" :active="$active" :title="'Prenosi uživo — Utakmice.me'" description="Gdje gledati prenose fudbalskih i košarkaških utakmica uživo — provjereni izvori za mečeve danas.">
    <div class="max-w-2xl mx-auto px-4 lg:px-0 py-5 lg:py-8 flex flex-col gap-5">

        <div class="hidden lg:flex flex-col gap-1">
            <h1 class="text-2xl font-extrabold tracking-tight">Prenosi uživo</h1>
            <p class="text-sm text-text-muted">
                @if ($liveNow)
                    <span class="inline-flex items-center gap-1.5 text-live-text font-semibold">
                        <span class="w-1.5 h-1.5 rounded-full bg-live animate-live"></span>
                        {{ $liveNow }} {{ $liveNow === 1 ? 'prenos uživo sada' : 'prenosa uživo sada' }}
                    </span>
                @else
                    Besplatni prenosi mečeva koje lige i savezi sami emituju uživo.
                @endif
            </p>
        </div>

        <p class="bg-surface border border-white/[0.07] rounded-2xl px-4 py-3 text-[13px] leading-relaxed text-text-muted">
            Ovdje su samo besplatni, zvanični prenosi — direktno sa kanala liga i saveza. Za mečeve liga petice i evropskih takmičenja prenosi su na plaćenim TV servisima.
        </p>

        @forelse ($groups as $day => $fixtures)
            @php $date = \Illuminate\Support\Carbon::parse($day); @endphp
            <div class="flex flex-col gap-3">
                <div class="flex items-center gap-2.5 px-1">
                    <span class="w-[3px] h-3.5 rounded {{ $accent['bg'] }}"></span>
                    <span class="font-mono text-[10px] font-bold tracking-[0.16em] text-text-2">{{ strtoupper(\App\Support\FootballFeed::dayLabel($date)) }}</span>
                    <span class="ml-auto font-mono text-[10px] text-text-dim">{{ $fixtures->count() }} {{ $fixtures->count() === 1 ? 'MEČ' : 'MEČA' }}</span>
                </div>

                <div class="flex flex-col gap-3">
                    @foreach ($fixtures as $fixture)
                        @php $stream = $fixture->streams->first(); @endphp
                        @if ($stream)
                            <div class="bg-surface border border-white/[0.07] rounded-2xl overflow-hidden">
                                <div class="relative aspect-video bg-black cursor-pointer group" data-stream-player data-embed="{{ $stream->embed_url }}">
                                    <img src="{{ $stream->thumbnail_url }}" alt="" loading="lazy" class="w-full h-full object-cover opacity-70 group-hover:opacity-90 transition-opacity">
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="w-14 h-14 rounded-full bg-black/60 border border-white/20 flex items-center justify-center">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="white"><path d="M8 5v14l11-7z"/></svg>
                                        </span>
                                    </div>
                                    @if ($fixture->isLive())
                                        <span class="absolute top-2.5 left-2.5 flex items-center gap-1.5 px-2 py-1 rounded-full bg-black/70 backdrop-blur">
                                            <span class="w-1.5 h-1.5 rounded-full bg-live animate-live"></span>
                                            <span class="font-mono text-[9px] font-bold text-live-text tracking-[0.08em]">UŽIVO</span>
                                        </span>
                                    @endif
                                </div>

                                <div class="p-3.5 flex items-center justify-between gap-3">
                                    <div class="flex flex-col gap-1 min-w-0">
                                        <span class="font-mono text-[9px] tracking-[0.1em] text-text-dim truncate">{{ strtoupper($fixture->league->name) }}</span>
                                        <span class="text-[13.5px] font-semibold truncate">{{ $fixture->homeTeam->name }} — {{ $fixture->awayTeam->name }}</span>
                                    </div>
                                    <a href="{{ \App\Support\Nav::match($fixture->id) }}" class="flex-none font-mono text-[10px] tracking-[0.05em] text-text-dim">Detalji &rsaquo;</a>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @empty
            <div class="border border-dashed border-white/[0.12] rounded-2xl p-8 text-center flex flex-col items-center gap-3">
                <span class="text-base font-bold">Trenutno nema prenosa</span>
                <p class="text-sm text-text-muted max-w-[40ch]">Čim neka liga ili savez pusti besplatan prenos meča, pojaviće se ovdje.</p>
                <a href="{{ \App\Support\Nav::scores($sport) }}" class="h-10 px-4.5 rounded-full flex items-center {{ $accent['bg'] }} text-bg text-sm font-bold">Vidi sve mečeve</a>
            </div>
        @endforelse

        @if ($sources->isNotEmpty())
            <div class="flex flex-col gap-3">
                <span class="font-mono text-[10px] font-bold tracking-[0.16em] text-text-2 px-1">IZVORI PRENOSA</span>
                <div class="bg-surface border border-white/[0.07] rounded-2xl overflow-hidden">
                    @foreach ($sources as $source)
                        <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5 px-3.5 py-3 {{ !$loop->last ? 'border-b border-white/[0.05]' : '' }}">
                            <a href="{{ $source->channel_url }}" target="_blank" rel="noopener nofollow" class="text-[13px] font-semibold {{ $accent['text'] }}">{{ $source->channel_name }}</a>
                            <span class="text-text-dim text-[12px]">·</span>
                            <span class="text-[12px] text-text-muted">{{ $source->league->name }}</span>
                        </div>
                    @endforeach
                </div>
                <p class="px-1 text-[11px] text-text-dim">Video sadržaj je vlasništvo navedenih kanala — mi samo povezujemo prenos sa mečom.</p>
            </div>
        @endif
    </div>

    <script>
        (function () {
            document.querySelectorAll('[data-stream-player]').forEach((el) => {
                el.addEventListener('click', () => {
                    const iframe = document.createElement('iframe');
                    iframe.src = el.dataset.embed;
                    iframe.className = 'w-full h-full';
                    iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
                    iframe.setAttribute('allowfullscreen', '');
                    iframe.setAttribute('frameborder', '0');
                    el.replaceChildren(iframe);
                    el.removeAttribute('data-stream-player');
                }, { once: true });
            });
        })();
    </script>
</x-layouts.app>
