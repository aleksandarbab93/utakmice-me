@props(['post', 'accent'])

<a href="{{ route('post.show', $post['slug']) }}" class="block bg-surface border border-white/[0.07] rounded-2xl overflow-hidden card-hover">
    <div class="h-38 img-placeholder flex items-center justify-center" style="height:150px">
        <span class="font-mono text-[10px] tracking-[0.14em] text-text-dim">FOTO SA TERENA 16:9</span>
    </div>
    <div class="p-3.5 flex flex-col gap-2">
        <span class="self-start font-mono text-[9px] font-bold tracking-[0.12em] px-2 py-1 rounded {{ $accent['tint'] }} {{ $accent['text'] }}">{{ strtoupper($post['league']) }}</span>
        <h3 class="text-lg font-extrabold leading-snug tracking-tight text-balance">{{ $post['title'] }}</h3>
        <span class="font-mono text-[9.5px] tracking-[0.1em] text-text-dim">{{ strtoupper($post['meta']) }}</span>
    </div>
</a>
