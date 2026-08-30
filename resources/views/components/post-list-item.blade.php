@props(['post'])

<a href="{{ route('post.show', $post['slug']) }}" class="flex flex-col gap-1.5">
    <span class="text-[14.5px] font-bold leading-snug">{{ $post['title'] }}</span>
    <span class="font-mono text-[9.5px] tracking-[0.1em] text-text-dim">{{ strtoupper($post['meta']) }}</span>
</a>
