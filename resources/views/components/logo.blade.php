@props(['size' => 'sm'])

@php
    $barHeight = $size === 'md' ? 'h-[20px]' : 'h-[16px]';
    $text = $size === 'md' ? 'text-[19px]' : 'text-[17px]';
@endphp

<span class="w-[3px] {{ $barHeight }} rounded-full bg-accent flex-none"></span>
<span class="{{ $text }} font-extrabold tracking-tight lowercase text-text">utakmice<span class="text-accent">.me</span></span>
