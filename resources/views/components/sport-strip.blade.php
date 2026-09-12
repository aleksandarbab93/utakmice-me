{{-- The sport switcher as a strip — icon, label, and the active one
     underlined in the accent colour — the way every results site in the
     region draws it. Where each link lands follows the page: on the
     Rezultati page it stays on Rezultati, on the tables page on tables. --}}
@props(['sport', 'active'])

@php
    $target = fn (string $s) => match ($active) {
        'scores' => \App\Support\Nav::scores($s),
        'standings' => \App\Support\Nav::standings($s),
        default => \App\Support\Nav::home($s),
    };
    $items = [
        ['key' => 'fudbal', 'label' => 'Fudbal', 'icon' => 'football'],
        ['key' => 'kosarka', 'label' => 'Košarka', 'icon' => 'basketball'],
    ];
@endphp

<nav {{ $attributes->class(['flex items-stretch gap-1 overflow-x-auto']) }} aria-label="Sport">
    @foreach ($items as $item)
        @php $isActive = $sport === $item['key']; @endphp
        <a href="{{ $target($item['key']) }}"
           class="flex items-center gap-2 px-3.5 h-11 border-b-2 font-mono text-[11px] font-bold tracking-[0.1em] whitespace-nowrap transition-colors {{ $isActive ? 'border-accent text-accent' : 'border-transparent text-text-muted hover:text-text-2' }}"
           @if ($isActive) aria-current="page" @endif>
            <x-icon :name="$item['icon']" class="w-4.5 h-4.5" />
            {{ mb_strtoupper($item['label'], 'UTF-8') }}
        </a>
    @endforeach
</nav>
