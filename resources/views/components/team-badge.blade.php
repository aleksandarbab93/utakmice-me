@props(['initials', 'crest' => null, 'class' => 'w-6 h-6 text-[9px]'])

<span class="{{ $class }} rounded-full bg-surface-2 border border-white/[0.08] flex-none flex items-center justify-center font-bold text-text-2 overflow-hidden relative">
    <span>{{ $initials }}</span>
    @if ($crest)
        <img src="{{ $crest }}" alt="" loading="lazy" class="absolute inset-0 w-full h-full object-contain bg-surface-2 p-0.5" onerror="this.remove()">
    @endif
</span>
