@props(['size' => 'sm'])

@php
    $ring = $size === 'md' ? 'w-[22px] h-[22px] border-[3px]' : 'w-5 h-5 border-2';
    $text = $size === 'md' ? 'text-[19px]' : 'text-[17px]';
@endphp

<span class="rounded-full border-brand {{ $ring }}"></span>
<span class="{{ $text }} font-extrabold tracking-tight">UTAKMICE<span class="text-brand">.ME</span></span>
