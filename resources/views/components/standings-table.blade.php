@props(['rows', 'accent'])

<div class="bg-surface border border-white/[0.07] rounded-2xl overflow-hidden">
    <div class="grid gap-2 px-3.5 py-2.5 border-b border-white/[0.07] font-mono text-[9px] tracking-[0.1em] text-text-dim" style="grid-template-columns:24px 1fr 32px 32px 40px">
        <span>#</span><span>KLUB</span><span class="text-center">M</span><span class="text-center">P</span><span class="text-right">+/&minus;</span>
    </div>
    @foreach ($rows as $row)
        <div class="grid gap-2 px-3.5 py-3 items-center {{ !$loop->last ? 'border-b border-white/[0.05]' : '' }} {{ $row['pos'] === 1 ? $accent['row'] : '' }}">
            <span class="font-mono text-xs font-bold {{ $row['pos'] === 1 ? $accent['text'] : 'text-text-muted' }}" style="grid-column:1">{{ $row['pos'] }}</span>
            <span class="text-[13.5px] {{ $row['pos'] === 1 ? 'font-bold' : 'font-semibold' }}" style="grid-column:2">{{ $row['team'] }}</span>
            <span class="font-mono text-xs text-text-2 text-center" style="grid-column:3">{{ $row['played'] }}</span>
            <span class="font-mono text-xs font-bold text-center" style="grid-column:4">{{ $row['points'] }}</span>
            <span class="font-mono text-xs text-right {{ str_starts_with($row['diff'], '+') ? 'text-positive' : (str_starts_with($row['diff'], '-') ? 'text-negative' : 'text-text-muted') }}" style="grid-column:5">{{ $row['diff'] }}</span>
        </div>
    @endforeach
</div>
