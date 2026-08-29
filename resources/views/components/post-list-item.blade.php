@props(['post'])

<a href="{{ route('post.show', $post['slug']) }}" class="flex gap-3 items-start">
    <div class="flex-none w-19 h-15 rounded-[10px] img-placeholder" style="width:76px;height:60px"></div>
    <div class="flex flex-col gap-1.5">
        <span class="text-[14.5px] font-bold leading-snug">{{ $post['title'] }}</span>
        <span class="font-mono text-[9.5px] tracking-[0.1em] text-text-dim">{{ strtoupper($post['meta']) }}</span>
    </div>
</a>
