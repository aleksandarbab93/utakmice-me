@php
    $months = [1=>'januar',2=>'februar',3=>'mart',4=>'april',5=>'maj',6=>'jun',7=>'jul',8=>'avgust',9=>'septembar',10=>'oktobar',11=>'novembar',12=>'decembar'];
    $weekdays = [1=>'ponedeljak',2=>'utorak',3=>'sreda',4=>'četvrtak',5=>'petak',6=>'subota',7=>'nedelja'];
    $dayLabel = function (\Illuminate\Support\Carbon $date) use ($months, $weekdays) {
        if ($date->isToday()) return 'Danas';
        if ($date->isTomorrow()) return 'Sutra';
        if ($date->isYesterday()) return 'Juče';

        return ucfirst($weekdays[$date->isoWeekday()]).', '.$date->day.'. '.$months[$date->month].' '.$date->year.'.';
    };
@endphp

<x-layouts.app :sport="$sport" :accent="$accent" :active="$active" :title="$league->name.' — '.($tab === 'results' ? 'Rezultati' : 'Raspored').' — Utakmice.me'">
    <div class="max-w-[1120px] mx-auto px-4 lg:px-7 py-5 lg:py-7 flex flex-col gap-4">
        <x-league-hero :league="$league" :club-count="$clubCount" :season="$season" />

        <x-league-tabs :league="$league" :accent="$accent" :active="$tab" />

        <div class="flex flex-col gap-4">
            @if ($fixtures->isEmpty())
                <div class="border border-dashed border-white/[0.12] rounded-2xl p-8 text-center text-text-muted text-sm">
                    {{ $tab === 'results' ? 'Još nema odigranih mečeva u ovom takmičenju.' : 'Trenutno nema zakazanih mečeva.' }}
                </div>
            @else
                @foreach ($fixtures->getCollection()->groupBy(fn ($f) => $f->kickoff_at->toDateString()) as $day => $dayFixtures)
                    <div class="bg-surface border border-white/[0.07] rounded-2xl overflow-hidden">
                        <div class="flex items-center justify-between gap-3 px-3.5 py-3 border-b border-white/[0.07]">
                            <span class="text-[13px] font-bold">{{ ucfirst($dayLabel($dayFixtures->first()->kickoff_at)) }}</span>
                            @if ($first = \App\Support\RoundLabel::sr($dayFixtures->first()->matchday))
                                <span class="font-mono text-[10px] text-text-dim flex-none">{{ $first }}</span>
                            @endif
                        </div>
                        @foreach ($dayFixtures as $f)
                            @php
                                $payload = [
                                    'id' => $f->id,
                                    'home' => $f->homeTeam->name,
                                    'homeInitials' => \App\Support\TeamBadge::initials($f->homeTeam->name),
                                    'homeCrest' => $f->homeTeam->crest_url,
                                    'away' => $f->awayTeam->name,
                                    'awayInitials' => \App\Support\TeamBadge::initials($f->awayTeam->name),
                                    'awayCrest' => $f->awayTeam->crest_url,
                                    'status' => $f->status,
                                    'home_score' => $f->home_score,
                                    'away_score' => $f->away_score,
                                    'minute' => $f->status === 'live' && $f->minute ? $f->minute."'" : null,
                                    'kickoff' => $f->kickoff_at->format('H:i'),
                                ];
                            @endphp
                            <x-score-row :match="$payload" />
                        @endforeach
                    </div>
                @endforeach

                <div class="pt-1">
                    <x-pagination :paginator="$fixtures" />
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
