<x-layouts.app :sport="$sport" :accent="$accent" :active="$active" :title="$page['title'].' — Utakmice.me'" :description="$page['description']">
    <div class="max-w-[720px] mx-auto px-4 lg:px-0 py-5 lg:py-10 flex flex-col gap-4">
        <div class="flex items-start gap-2.5 lg:gap-3">
            <span class="flex-none w-1 h-6 lg:h-8 rounded-[2px] {{ $accent['bg'] }} mt-0.5"></span>
            <div class="flex flex-col gap-1.5 lg:gap-2">
                <h1 class="text-2xl lg:text-[32px] font-extrabold tracking-tight leading-none">{{ $page['title'] }}</h1>
                <p class="text-[13.5px] lg:text-[15px] leading-relaxed text-text-muted max-w-[60ch]">{{ $page['lead'] }}</p>
            </div>
        </div>

        <div class="flex flex-col gap-4 mt-2">
            @foreach ($page['body'] as $paragraph)
                <p class="text-[15px] lg:text-base leading-relaxed text-text-2 max-w-[68ch]">{{ $paragraph }}</p>
            @endforeach
        </div>
    </div>
</x-layouts.app>
