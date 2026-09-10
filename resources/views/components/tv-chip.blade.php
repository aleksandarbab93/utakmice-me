{{-- The channel a match is on. $mute is for anything that isn't a live
     broadcast — a note that it has already been played, say. --}}
@props(['mute' => false])

<span {{ $attributes->class([
    'font-mono text-[9.5px] tracking-[0.04em] whitespace-nowrap rounded px-1.5 py-0.5 border inline-block',
    'text-accent border-accent/35 bg-accent/5' => ! $mute,
    'text-text-dim border-white/[0.08] bg-transparent' => $mute,
]) }}>{{ $slot }}</span>
