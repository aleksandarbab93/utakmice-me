<?php

namespace App\Support;

/**
 * Placeholder content standing in for the future DB-backed pipeline
 * (football-data.org / API-Basketball sync + RSS aggregation).
 *
 * Business rule: a post of type "izvestaj" only exists for a match whose
 * status is "finished" — there are deliberately no pre-match preview posts.
 * "vest" posts are general sports news not tied to a specific match.
 */
class SampleData
{
    /**
     * Home page "Uživo / Danas / Sutra" widget — a quick-glance view,
     * separate from the full per-league results on the Rezultati page.
     */
    public static function homeLive(string $sport): array
    {
        return $sport === 'kosarka' ? self::basketballHomeLive() : self::footballHomeLive();
    }

    private static function footballHomeLive(): array
    {
        return [
            'uzivo' => [
                ['league' => 'PREMIJER LIGA', 'status' => "62'", 'live' => true, 'home' => 'Arsenal', 'away' => 'Čelsi', 'hs' => '2', 'as' => '1'],
                ['league' => 'LA LIGA', 'status' => "38'", 'live' => true, 'home' => 'Barselona', 'away' => 'Sevilja', 'hs' => '1', 'as' => '0'],
                ['league' => 'BUNDESLIGA', 'status' => "71'", 'live' => true, 'home' => 'Bajern', 'away' => 'Lajpcig', 'hs' => '3', 'as' => '2'],
                ['league' => 'LIGUE 1', 'status' => "24'", 'live' => true, 'home' => 'PSŽ', 'away' => 'Lion', 'hs' => '0', 'as' => '0'],
            ],
            'danas' => [
                ['league' => 'SERIE A', 'status' => '20:45', 'live' => false, 'home' => 'Inter', 'away' => 'Juventus', 'hs' => '–', 'as' => '–'],
                ['league' => 'PREMIJER LIGA', 'status' => '21:00', 'live' => false, 'home' => 'Liverpul', 'away' => 'Njukasl', 'hs' => '–', 'as' => '–'],
                ['league' => 'LA LIGA', 'status' => '21:00', 'live' => false, 'home' => 'Real Madrid', 'away' => 'Betis', 'hs' => '–', 'as' => '–'],
            ],
            'sutra' => [
                ['league' => 'BUNDESLIGA', 'status' => '15:30', 'live' => false, 'home' => 'Dortmund', 'away' => 'Verder', 'hs' => '–', 'as' => '–'],
                ['league' => 'SERIE A', 'status' => '18:00', 'live' => false, 'home' => 'Napoli', 'away' => 'Roma', 'hs' => '–', 'as' => '–'],
            ],
        ];
    }

    private static function basketballHomeLive(): array
    {
        return [
            'uzivo' => [
                ['league' => 'EVROLIGA', 'status' => 'Q3', 'live' => true, 'home' => 'Partizan', 'away' => 'Panatinaikos', 'hs' => '68', 'as' => '61'],
                ['league' => 'EVROLIGA', 'status' => 'Q2', 'live' => true, 'home' => 'Real Madrid', 'away' => 'Fenerbahče', 'hs' => '41', 'as' => '44'],
                ['league' => 'EVROKUP', 'status' => 'Q4', 'live' => true, 'home' => 'Hapoel', 'away' => 'Bahčešehir', 'hs' => '77', 'as' => '70'],
            ],
            'danas' => [
                ['league' => 'EVROLIGA', 'status' => '20:30', 'live' => false, 'home' => 'Crvena zvezda', 'away' => 'Olimpijakos', 'hs' => '–', 'as' => '–'],
                ['league' => 'EVROKUP', 'status' => '19:00', 'live' => false, 'home' => 'Cedevita Olimpija', 'away' => 'Umana Reyer', 'hs' => '–', 'as' => '–'],
            ],
            'sutra' => [
                ['league' => 'EVROLIGA', 'status' => '20:00', 'live' => false, 'home' => 'Makabi', 'away' => 'Virtus', 'hs' => '–', 'as' => '–'],
                ['league' => 'EVROLIGA', 'status' => '20:45', 'live' => false, 'home' => 'Baskonija', 'away' => 'Asvel', 'hs' => '–', 'as' => '–'],
            ],
        ];
    }

    public static function matches(string $sport): array
    {
        return $sport === 'kosarka' ? self::basketballMatches() : self::footballMatches();
    }

    public static function matchBySlug(string $slug): ?array
    {
        foreach (array_merge(self::footballMatches(), self::basketballMatches()) as $match) {
            if ($match['slug'] === $slug) {
                return $match;
            }
        }

        return null;
    }

    public static function posts(string $sport): array
    {
        return $sport === 'kosarka' ? self::basketballPosts() : self::footballPosts();
    }

    public static function postBySlug(string $slug): ?array
    {
        foreach (array_merge(self::footballPosts(), self::basketballPosts()) as $post) {
            if ($post['slug'] === $slug) {
                $post['match'] = $post['match_slug'] ? self::matchBySlug($post['match_slug']) : null;

                return $post;
            }
        }

        return null;
    }

    public static function standings(string $sport): array
    {
        return $sport === 'kosarka'
            ? [
                'competition' => 'Evroliga',
                'competitions' => ['Evroliga', 'Evrokup'],
                'rows' => [
                    ['pos' => 1, 'team' => 'Olimpijakos', 'played' => 22, 'points' => 17, 'diff' => '+142'],
                    ['pos' => 2, 'team' => 'Real Madrid', 'played' => 22, 'points' => 16, 'diff' => '+118'],
                    ['pos' => 3, 'team' => 'Partizan', 'played' => 22, 'points' => 14, 'diff' => '+61'],
                    ['pos' => 4, 'team' => 'Crvena zvezda', 'played' => 22, 'points' => 13, 'diff' => '+24'],
                    ['pos' => 5, 'team' => 'Fenerbahče', 'played' => 22, 'points' => 12, 'diff' => '+9'],
                    ['pos' => 6, 'team' => 'Panatinaikos', 'played' => 22, 'points' => 11, 'diff' => '-14'],
                ],
                'next' => ['label' => 'Partizan — Real Madrid', 'when' => 'SUB 20:30'],
            ]
            : [
                'competition' => 'Premijer liga',
                'competitions' => ['Premijer liga', 'La Liga', 'Serie A', 'Bundesliga', 'Ligue 1'],
                'rows' => [
                    ['pos' => 1, 'team' => 'Arsenal', 'played' => 24, 'points' => 54, 'diff' => '+31'],
                    ['pos' => 2, 'team' => 'Mančester Siti', 'played' => 24, 'points' => 51, 'diff' => '+28'],
                    ['pos' => 3, 'team' => 'Liverpul', 'played' => 24, 'points' => 49, 'diff' => '+25'],
                    ['pos' => 4, 'team' => 'Totenhem', 'played' => 24, 'points' => 45, 'diff' => '+14'],
                    ['pos' => 5, 'team' => 'Čelsi', 'played' => 24, 'points' => 43, 'diff' => '+11'],
                    ['pos' => 6, 'team' => 'Aston Vila', 'played' => 24, 'points' => 40, 'diff' => '+8'],
                ],
                'next' => ['label' => 'Arsenal — Mančester Siti', 'when' => 'SUB 17:30'],
            ];
    }

    private static function footballMatches(): array
    {
        return [
            ['slug' => 'arsenal-celsi', 'league' => 'Premijer liga', 'home' => 'Arsenal', 'away' => 'Čelsi', 'status' => 'finished', 'home_score' => 2, 'away_score' => 1, 'venue' => 'Emirates', 'round' => '24. kolo'],
            ['slug' => 'barselona-sevilja', 'league' => 'La Liga', 'home' => 'Barselona', 'away' => 'Sevilja', 'status' => 'finished', 'home_score' => 2, 'away_score' => 1, 'venue' => 'Camp Nou', 'round' => '23. kolo'],
            ['slug' => 'inter-juventus', 'league' => 'Serie A', 'home' => 'Inter', 'away' => 'Juventus', 'status' => 'scheduled', 'kickoff' => '20:45'],
            ['slug' => 'bajern-lajpcig', 'league' => 'Bundesliga', 'home' => 'Bajern', 'away' => 'Lajpcig', 'status' => 'live', 'minute' => "71'", 'home_score' => 3, 'away_score' => 0],
        ];
    }

    private static function basketballMatches(): array
    {
        return [
            ['slug' => 'partizan-panatinaikos', 'league' => 'Evroliga', 'home' => 'Partizan', 'away' => 'Panatinaikos', 'status' => 'live', 'period' => 'Q3', 'home_score' => 68, 'away_score' => 61],
            ['slug' => 'real-madrid-fenerbahce', 'league' => 'Evroliga', 'home' => 'Real Madrid', 'away' => 'Fenerbahče', 'status' => 'scheduled', 'kickoff' => '20:00'],
            ['slug' => 'zvezda-olimpijakos', 'league' => 'Evroliga', 'home' => 'Crvena zvezda', 'away' => 'Olimpijakos', 'status' => 'finished', 'home_score' => 91, 'away_score' => 84, 'venue' => 'Štark Arena', 'round' => '22. kolo'],
            ['slug' => 'cedevita-umana', 'league' => 'Evrokup', 'home' => 'Cedevita Olimpija', 'away' => 'Umana Reyer', 'status' => 'finished', 'home_score' => 89, 'away_score' => 82, 'venue' => 'Stožice', 'round' => '18. kolo'],
            ['slug' => 'hapoel-bahcesehir', 'league' => 'Evrokup', 'home' => 'Hapoel Tel Aviv', 'away' => 'Bahčešehir', 'status' => 'scheduled', 'kickoff' => '19:00'],
        ];
    }

    private static function footballPosts(): array
    {
        return [
            [
                'slug' => 'arsenal-do-prednosti-golom-u-nadoknadi',
                'type' => 'izvestaj',
                'match_slug' => 'arsenal-celsi',
                'league' => 'Premijer liga',
                'title' => 'Arsenal do prednosti golom u nadoknadi prvog dela',
                'lead' => 'Domaći su odigrali najbolju deonicu sezone i kaznili grešku gostujuće odbrane pred odlazak na odmor.',
                'meta' => 'Pre 12 minuta',
                'read_minutes' => 3,
                'author' => 'Marko Ilić',
                'body' => [
                    'Gosti su prvih dvadeset minuta držali posed, ali bez ozbiljnog udarca ka golu. Prelom je došao posle prekida na desnoj strani, kada je odbrana ostavila prostor u centru i dozvolila udarac sa ivice šesnaest metara.',
                    'U nastavku se očekuje izmena u sredini terena, pošto je kapiten igrao sa žutim kartonom od 30. minuta. Domaći su u poslednjih pet kola primili samo dva gola.',
                ],
                'quote' => ['text' => 'Znali smo da će prostora biti posle prekida.', 'attribution' => 'TRENER DOMAĆIH, POSLE PRVOG DELA'],
                'tags' => ['Arsenal', 'Čelsi', 'Premijer liga'],
            ],
            [
                'slug' => 'barselona-slavila-gol-u-finisu',
                'type' => 'izvestaj',
                'match_slug' => 'barselona-sevilja',
                'league' => 'La Liga',
                'title' => 'Barselona slavila protiv Sevilje uz gol u finišu',
                'lead' => 'Katalonci su morali da sačekaju do poslednjih minuta da bi stigli do sva tri boda na svom terenu.',
                'meta' => 'Pre 1 sat',
                'read_minutes' => 2,
                'author' => 'Ana Petrović',
                'body' => [
                    'Sevilja je dobro zatvarala prostor kroz sredinu terena i držala rezultat izjednačenim sve do 86. minuta, kada je promenom na krilu domaći tim otvorio prostor za odlučujući udarac.',
                ],
                'quote' => null,
                'tags' => ['Barselona', 'Sevilja', 'La Liga'],
            ],
            [
                'slug' => 'bajern-bez-povredjenog-kapitena',
                'type' => 'vest',
                'match_slug' => null,
                'league' => 'Bundesliga',
                'title' => 'Bajern bez povređenog kapitena u derbiju',
                'lead' => 'Klupski lekari procenjuju da će kapiten propustiti narednih nekoliko utakmica zbog povrede zadobijene na treningu.',
                'meta' => 'Pre 2 sata',
                'read_minutes' => 1,
                'author' => null,
                'body' => [],
                'quote' => null,
                'tags' => ['Bajern', 'Bundesliga'],
            ],
            [
                'slug' => 'ligue1-novi-format-plej-ofa',
                'type' => 'vest',
                'match_slug' => null,
                'league' => 'Ligue 1',
                'title' => 'Ligue 1 uvodi novi format plej-ofa od naredne sezone',
                'lead' => 'Uprava lige je predstavila predlog izmena koji bi trebalo da poveća neizvesnost u borbi za vrh tabele.',
                'meta' => 'Pre 3 sata',
                'read_minutes' => 2,
                'author' => null,
                'body' => [],
                'quote' => null,
                'tags' => ['Ligue 1'],
            ],
            [
                'slug' => 'zvezda-reala-van-terena',
                'type' => 'vest',
                'match_slug' => null,
                'league' => 'La Liga',
                'title' => 'Zvezda Reala van terena zbog povrede mišića',
                'lead' => 'Klupski lekari procenjuju pauzu od nekoliko nedelja pred važan deo sezone.',
                'meta' => 'Pre 4 sata',
                'read_minutes' => 1,
                'author' => null,
                'body' => [],
                'quote' => null,
                'tags' => ['Real Madrid', 'La Liga'],
            ],
            [
                'slug' => 'var-analiza-spornih-odluka',
                'type' => 'vest',
                'match_slug' => null,
                'league' => 'Premijer liga',
                'title' => 'VAR analiza: sporne odluke iz prošlog kola',
                'lead' => 'Bivši sudija za naš portal komentariše tri najviše osporavane odluke vikenda.',
                'meta' => 'Pre 6 sati',
                'read_minutes' => 3,
                'author' => null,
                'body' => [],
                'quote' => null,
                'tags' => ['Premijer liga', 'Suđenje'],
            ],
            [
                'slug' => 'milan-napoli-pregovori',
                'type' => 'vest',
                'match_slug' => null,
                'league' => 'Serie A',
                'title' => 'Milan i Napoli otvaraju pregovore o razmeni igrača',
                'lead' => 'Oba kluba traže pojačanje na istim pozicijama pred drugi deo sezone.',
                'meta' => 'Pre 7 sati',
                'read_minutes' => 2,
                'author' => null,
                'body' => [],
                'quote' => null,
                'tags' => ['Milan', 'Napoli', 'Serie A'],
            ],
        ];
    }

    private static function basketballPosts(): array
    {
        return [
            [
                'slug' => 'zvezda-preokretom-do-pobede',
                'type' => 'izvestaj',
                'match_slug' => 'zvezda-olimpijakos',
                'league' => 'Evroliga',
                'title' => 'Crvena zvezda slavila protiv Olimpijakosa pred punom Arenom',
                'lead' => 'Crno-beli iz Beograda su nadoknadili deficit u poslednjoj deonici i stigli do važne pobede u vrhu tabele.',
                'meta' => 'Pre 40 minuta',
                'read_minutes' => 2,
                'author' => 'Nikola Radić',
                'body' => [
                    'Gosti su vodili sve do poslednje četvrtine, ali su domaći ubrzali tempo i preko sedam poena u nizu preokrenuli utakmicu.',
                ],
                'quote' => ['text' => 'Publika nas je nosila u poslednjih pet minuta.', 'attribution' => 'KAPITEN DOMAĆIH, POSLE MEČA'],
                'tags' => ['Crvena zvezda', 'Olimpijakos', 'Evroliga'],
            ],
            [
                'slug' => 'cedevita-iznenadila-umanu-reyer',
                'type' => 'izvestaj',
                'match_slug' => 'cedevita-umana',
                'league' => 'Evrokup',
                'title' => 'Cedevita Olimpija iznenadila Umanu Reyer u gostima',
                'lead' => 'Slovenački sastav je sigurno čuvao prednost u poslednjih pet minuta i doneo bod protiv favorizovanog domaćina.',
                'meta' => 'Pre 2 sata',
                'read_minutes' => 2,
                'author' => 'Nikola Radić',
                'body' => [
                    'Odbrana gostiju je u poslednjoj deonici dozvolila samo šest poena domaćinu, što je bilo dovoljno za iznenađenje kola.',
                ],
                'quote' => null,
                'tags' => ['Cedevita Olimpija', 'Umana Reyer', 'Evrokup'],
            ],
            [
                'slug' => 'transfer-glasine-nba-plejmejker',
                'type' => 'vest',
                'match_slug' => null,
                'league' => 'Evroliga',
                'title' => 'Transfer glasine: NBA plejmejker blizu potpisa u Evropi',
                'lead' => 'Prema pisanju stranih medija, dogovor bi mogao biti zaključen do kraja meseca.',
                'meta' => 'Pre 4 sata',
                'read_minutes' => 1,
                'author' => null,
                'body' => [],
                'quote' => null,
                'tags' => ['Evroliga', 'Transferi'],
            ],
            [
                'slug' => 'evrokup-izmena-formata',
                'type' => 'vest',
                'match_slug' => null,
                'league' => 'Evrokup',
                'title' => 'Klubovi razmatraju izmenu formata takmičenja',
                'lead' => 'Rukovodstvo lige najavilo je sastanak na kome će biti reči o proširenju broja učesnika.',
                'meta' => 'Pre 6 sati',
                'read_minutes' => 1,
                'author' => null,
                'body' => [],
                'quote' => null,
                'tags' => ['Evrokup'],
            ],
            [
                'slug' => 'povreda-centra-evroliga',
                'type' => 'vest',
                'match_slug' => null,
                'league' => 'Evroliga',
                'title' => 'Ključni centar propušta naredna dva kola zbog povrede',
                'lead' => 'Klub je saopštio da će oporavak trajati oko dve nedelje.',
                'meta' => 'Pre 5 sati',
                'read_minutes' => 1,
                'author' => null,
                'body' => [],
                'quote' => null,
                'tags' => ['Evroliga', 'Povrede'],
            ],
            [
                'slug' => 'borba-za-plej-of-evrokup',
                'type' => 'vest',
                'match_slug' => null,
                'league' => 'Evrokup',
                'title' => 'Borba za plej-of sve neizvesnija na sredini sezone',
                'lead' => 'Šest klubova deli svega dva boda u sredini tabele.',
                'meta' => 'Pre 7 sati',
                'read_minutes' => 2,
                'author' => null,
                'body' => [],
                'quote' => null,
                'tags' => ['Evrokup'],
            ],
            [
                'slug' => 'smena-na-klupi-evroliga',
                'type' => 'vest',
                'match_slug' => null,
                'league' => 'Evroliga',
                'title' => 'Klub iz Grčke najavio smenu na klupi',
                'lead' => 'Uprava kluba navodi seriju neubedljivih partija kao glavni razlog odluke.',
                'meta' => 'Pre 8 sati',
                'read_minutes' => 1,
                'author' => null,
                'body' => [],
                'quote' => null,
                'tags' => ['Evroliga'],
            ],
        ];
    }
}
