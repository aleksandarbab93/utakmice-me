@props(['icon'])

@php
    $classes = match ($icon) {
        'goal' => 'text-accent',
        'og' => 'text-live-text',
        'miss' => 'text-text-dim',
        'cancel' => 'text-text-dim line-through',
        'yellow' => '',
        'red' => '',
        'sub' => 'text-text-dim',
        default => 'text-text-dim',
    };
@endphp

@if ($icon === 'yellow')
    <span class="flex-none w-2.5 h-3.5 rounded-[2px] bg-yellow-400"></span>
@elseif ($icon === 'red')
    <span class="flex-none w-2.5 h-3.5 rounded-[2px] bg-red-500"></span>
@elseif ($icon === 'sub')
    <span class="flex-none font-mono text-[11px] {{ $classes }}">&#8644;</span>
@else
    <span class="flex-none text-[13px] {{ $classes }}">&#9917;</span>
@endif
