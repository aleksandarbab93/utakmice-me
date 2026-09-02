@props(['icon', 'class' => 'w-4 h-3'])

@if (! empty($icon['url']))
    <span class="{{ $class }} rounded-[2px] overflow-hidden inline-flex items-center justify-center flex-none {{ $icon['type'] === 'badge' ? 'bg-white p-px' : '' }}">
        <img src="{{ $icon['url'] }}" alt="" loading="lazy" class="w-full h-full {{ $icon['type'] === 'badge' ? 'object-contain' : 'object-cover' }}" onerror="this.parentElement.remove()">
    </span>
@endif
