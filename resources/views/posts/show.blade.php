@php
    $match = $post['match'] ?? null;
    $matchIsLive = $match && $match['status'] === 'live';
    $author = $post['author'] ?? 'Redakcija';
@endphp

<x-layouts.app :sport="$sport" :accent="$accent" :active="$active" :title="$post['title'].' — Utakmice.me'" :description="$post['lead']">

    {{-- Mobile --}}
    <div class="lg:hidden flex flex-col">
        <div class="px-4 pt-3.5 flex items-center justify-between">
            <span class="font-mono text-[10px] tracking-[0.14em] {{ $accent['text'] }}">{{ strtoupper($post['league']) }}</span>
            <div class="flex gap-2">
                <button class="w-8 h-8 rounded-full bg-surface border border-white/[0.08] flex items-center justify-center text-text-muted"><x-icon name="star" class="w-3.5 h-3.5" /></button>
                <button class="w-8 h-8 rounded-full bg-surface border border-white/[0.08] flex items-center justify-center text-text-muted"><x-icon name="share" class="w-3.5 h-3.5" /></button>
            </div>
        </div>

        <div class="flex-1 {{ $matchIsLive ? 'pb-20' : '' }}">
            <div class="px-4 py-4.5 flex flex-col gap-3.5">
                <h1 class="text-[26px] font-extrabold leading-tight tracking-tight text-balance">{{ $post['title'] }}</h1>
                <p class="text-base leading-relaxed text-text-2">{{ $post['lead'] }}</p>

                <div class="flex items-center gap-2.5 py-3 border-y border-white/[0.07]">
                    <span class="w-7.5 h-7.5 rounded-full bg-panel" style="width:30px;height:30px"></span>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-[12.5px] font-semibold">{{ $author }}</span>
                        <span class="font-mono text-[9px] tracking-[0.1em] text-text-dim">{{ strtoupper($post['meta']) }} &middot; {{ $post['read_minutes'] }} MIN ČITANJA</span>
                    </div>
                </div>

                @if ($match)
                    <div class="bg-surface border border-white/[0.07] rounded-2xl p-3.5 flex flex-col gap-2.5">
                        <div class="flex justify-between items-center">
                            <span class="font-mono text-[9px] tracking-[0.12em] text-text-dim">{{ strtoupper(($match['venue'] ?? '').' · '.($match['round'] ?? '')) }}</span>
                            @if ($match['status'] === 'live')
                                <span class="flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-live animate-live"></span>
                                    <span class="font-mono text-[9px] font-bold text-live-text">{{ $match['minute'] ?? $match['period'] ?? '' }}</span>
                                </span>
                            @endif
                        </div>
                        <div class="flex justify-between"><span class="text-[15px] font-bold">{{ $match['home'] }}</span><span class="font-mono text-lg font-bold">{{ $match['home_score'] }}</span></div>
                        <div class="flex justify-between text-text-muted"><span class="text-[15px]">{{ $match['away'] }}</span><span class="font-mono text-lg font-bold">{{ $match['away_score'] }}</span></div>
                    </div>
                @endif

                @foreach ($post['body'] as $paragraph)
                    <p class="text-base leading-relaxed text-text-2">{{ $paragraph }}</p>
                @endforeach

                @if ($post['quote'])
                    <div class="pl-4 border-l-[3px] {{ $accent['border'] }} flex flex-col gap-1.5 py-1">
                        <p class="text-lg font-bold leading-snug tracking-tight">&ldquo;{{ $post['quote']['text'] }}&rdquo;</p>
                        <span class="font-mono text-[9px] tracking-[0.1em] text-text-dim">{{ $post['quote']['attribution'] }}</span>
                    </div>
                @endif
            </div>
        </div>

        @if ($matchIsLive)
            <div class="h-16 border-t border-white/[0.07] bg-[#0A0C0F] flex items-center gap-2.5 px-4 fixed bottom-0 inset-x-0 z-10">
                <div class="flex-1 h-11 rounded-full flex items-center justify-center {{ $accent['tint'] }} border {{ $accent['tintBorder'] }} {{ $accent['text'] }} text-sm font-bold">Prati meč uživo</div>
                <button class="w-11 h-11 rounded-full bg-surface border border-white/[0.08] flex items-center justify-center text-text-muted"><x-icon name="share" class="w-4 h-4" /></button>
            </div>
        @endif
    </div>

    {{-- Desktop --}}
    <div class="hidden lg:block max-w-[1000px] mx-auto py-10">
        <div class="flex items-center gap-2.5 font-mono text-[10px] tracking-[0.12em] text-text-dim mb-5">
            <span class="{{ $accent['text'] }}">{{ strtoupper($sport === 'kosarka' ? 'KOŠARKA' : 'FUDBAL') }}</span>
            <span>&rsaquo;</span>
            <span class="{{ $accent['text'] }}">{{ strtoupper($post['league']) }}</span>
        </div>
        <div class="grid gap-8" style="grid-template-columns:1fr 300px">
            <div class="flex flex-col gap-4.5">
                <h1 class="text-[42px] font-extrabold leading-tight tracking-tight text-balance max-w-[16ch]">{{ $post['title'] }}</h1>
                <p class="text-lg leading-relaxed text-text-muted max-w-[60ch]">{{ $post['lead'] }}</p>

                <div class="flex items-center gap-3 py-3.5 border-y border-white/[0.07]">
                    <span class="w-8.5 h-8.5 rounded-full bg-panel" style="width:34px;height:34px"></span>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-[13px] font-semibold">{{ $author }}</span>
                        <span class="font-mono text-[9.5px] tracking-[0.1em] text-text-dim">{{ strtoupper($post['meta']) }} &middot; {{ $post['read_minutes'] }} MIN ČITANJA</span>
                    </div>
                    <div class="ml-auto flex gap-2">
                        <span class="h-8 px-3.5 rounded-full bg-surface border border-white/[0.08] flex items-center text-text-muted text-[12.5px]">Podeli</span>
                        <span class="h-8 px-3.5 rounded-full bg-surface border border-white/[0.08] flex items-center text-text-muted text-[12.5px]">Sačuvaj</span>
                    </div>
                </div>

                @foreach ($post['body'] as $paragraph)
                    <p class="text-[17px] leading-relaxed text-text-2 max-w-[68ch]">{{ $paragraph }}</p>
                @endforeach

                @if ($post['quote'])
                    <div class="pl-5 border-l-[3px] {{ $accent['border'] }} flex flex-col gap-2 py-1.5 max-w-[60ch]">
                        <p class="text-2xl font-bold leading-snug tracking-tight">&ldquo;{{ $post['quote']['text'] }}&rdquo;</p>
                        <span class="font-mono text-[9.5px] tracking-[0.1em] text-text-dim">{{ $post['quote']['attribution'] }}</span>
                    </div>
                @endif

                <div class="flex flex-wrap gap-2 pt-1.5">
                    @foreach ($post['tags'] as $tag)
                        <span class="h-8 px-3.5 rounded-full bg-surface border border-white/[0.08] flex items-center text-[12.5px] text-text-2">{{ $tag }}</span>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col gap-3.5">
                @if ($match)
                    <div class="bg-surface border border-white/[0.07] rounded-2xl overflow-hidden">
                        <div class="px-3.5 py-3 border-b border-white/[0.07] flex justify-between items-center">
                            <span class="font-mono text-[10px] font-bold tracking-[0.14em] text-text-2">MEČ</span>
                            @if ($match['status'] === 'live')
                                <span class="flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-live animate-live"></span>
                                    <span class="font-mono text-[9px] font-bold text-live-text">{{ $match['minute'] ?? $match['period'] ?? '' }}</span>
                                </span>
                            @endif
                        </div>
                        <div class="p-3.5 flex flex-col gap-2.5">
                            <div class="flex justify-between"><span class="text-[14.5px] font-bold">{{ $match['home'] }}</span><span class="font-mono text-base font-bold">{{ $match['home_score'] }}</span></div>
                            <div class="flex justify-between text-text-muted"><span class="text-[14.5px]">{{ $match['away'] }}</span><span class="font-mono text-base font-bold">{{ $match['away_score'] }}</span></div>
                            <a href="{{ \App\Support\Nav::match($match['id']) }}" class="h-9.5 rounded-full flex items-center justify-center {{ $accent['bg'] }} text-bg text-[13px] font-bold" style="height:38px">Tok meča</a>
                        </div>
                    </div>
                @endif
                <div class="bg-surface border border-white/[0.07] rounded-2xl overflow-hidden">
                    <div class="px-3.5 py-3 border-b border-white/[0.07] font-mono text-[10px] font-bold tracking-[0.14em] text-text-2">POVEZANO</div>
                    @foreach ($related as $rp)
                        <a href="{{ route('post.show', $rp['slug']) }}" class="flex px-3.5 py-3 {{ !$loop->last ? 'border-b border-white/[0.05]' : '' }}">
                            <span class="text-[13px] font-semibold leading-snug">{{ $rp['title'] }}</span>
                        </a>
                    @endforeach
                </div>
                <div class="border border-dashed border-white/[0.12] rounded-2xl p-4 flex flex-col gap-2">
                    <span class="font-mono text-[9px] tracking-[0.14em] text-text-dim">REKLAMNI BLOK 300&times;250</span>
                    <div class="h-22 rounded-lg img-placeholder" style="height:90px"></div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
