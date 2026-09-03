<?php

namespace App\Http\Controllers;

use App\Support\Accent;

/**
 * A handful of informational pages (about, contact, privacy, advertising)
 * that don't need a database row each — just real, honest copy about what
 * this site actually does and what it does and doesn't collect.
 */
class StaticPageController extends Controller
{
    private const PAGES = [
        'o-nama' => [
            'title' => 'O nama',
            'description' => 'Ko stoji iza utakmice.me i šta pratimo — fudbal i košarka, rezultati uživo i izveštaji sa mečeva.',
            'lead' => 'utakmice.me je nezavisan sajt za praćenje fudbala i košarke — rezultati uživo, tabele i izveštaji sa mečeva, bez čekanja na osvežavanje strane.',
            'body' => [
                'Pratimo fudbal iz "lige petice" (Premijer liga, La Liga, Serie A, Bundesliga, Ligue 1), evropska klupska takmičenja (Liga prvaka, Evropska liga, Liga konferencija) i regionalne lige — Superligu Srbije, Prvu crnogorsku ligu, Premijer ligu BiH, HNL, 1. SNL i Prvu ligu Makedonije. Iz košarke pratimo Evroligu i Evrokup.',
                'Rezultati i tabele osvežavaju se uživo, u ritmu kojim se mečevi zaista igraju. Izveštaji sa mečeva u sekciji Vijesti pišu se na osnovu stvarnog toka meča — golova, poluvremena i statistike — nikad izmišljenih detalja.',
                'Sajt nije povezan ni sa jednim klubom, ligom ili savezom. Svi nazivi klubova i takmičenja pripadaju njihovim vlasnicima i navedeni su isključivo u informativne svrhe.',
            ],
        ],
        'kontakt' => [
            'title' => 'Kontakt',
            'description' => 'Pitanja, primjedbe ili saradnja — kako da nas kontaktirate.',
            'lead' => 'Javite nam se za pitanja u vezi sa sadržajem, greške u rezultatima ili predloge.',
            'body' => [
                'Za sva pitanja, primjedbe ili prijavu greške u rezultatu ili tabeli, pišite nam na redakcija@utakmice.me.',
                'Za saradnju i oglašavanje, pogledajte stranicu Oglašavanje ili pišite direktno na oglasavanje@utakmice.me.',
                'Trudimo se da odgovorimo u roku od nekoliko dana.',
            ],
        ],
        'privatnost' => [
            'title' => 'Politika privatnosti',
            'description' => 'Šta utakmice.me prikuplja, a šta ne — favoriti, obaveštenja o golovima i kolačići.',
            'lead' => 'Sajt radi bez naloga i bez registracije. Evo tačno šta se čuva, gde i zašto.',
            'body' => [
                'Favoriti. Lista mečeva koje pratite (klik na zvjezdicu) čuva se isključivo lokalno u vašem pretraživaču (localStorage) i ne šalje se na server — osim ako sami uključite obaveštenja o golovima za favorizovane mečeve.',
                'Obaveštenja o golovima. Ako ih uključite, pretraživač generiše tehnički "push endpoint" preko kog vam server može poslati obaveštenje, i šalje ga zajedno sa listom mečeva koje pratite. Ne tražimo ime, email ni bilo koji drugi lični podatak — endpoint identifikuje uređaj, ne osobu. Isključivanjem obaveštenja ovaj zapis se briše.',
                'Kolačići. Koristimo samo jedan, tehnički neophodan kolačić (Laravel sesija) da bi sajt uopšte radio — nema reklamnih kolačića niti kolačića za praćenje trećih strana.',
                'Server logovi. Kao i svaki sajt, privremeno beležimo IP adresu i osnovne tehničke podatke o zahtjevu radi bezbjednosti i sprečavanja zloupotrebe, ne radi profilisanja posjetilaca.',
                'Pitanja o privatnosti možete poslati na stranici Kontakt.',
            ],
        ],
        'oglasavanje' => [
            'title' => 'Oglašavanje',
            'description' => 'Oglasni prostor na utakmice.me — publika, formati i kontakt za saradnju.',
            'lead' => 'utakmice.me okuplja čitaoce koji svakodnevno prate rezultate fudbala i košarke uživo.',
            'body' => [
                'Naša publika prati "ligu petice", evropska klupska takmičenja, regionalni fudbal i evropsku košarku — iz Srbije, Crne Gore i regiona.',
                'Sajt je u ranoj fazi razvoja i oglasni formati (bannerski prostor, sponzorisan sadržaj) trenutno se dogovaraju individualno.',
                'Za saradnju i medijski kit pišite na oglasavanje@utakmice.me.',
            ],
        ],
    ];

    public function show(string $slug)
    {
        $page = self::PAGES[$slug] ?? abort(404);
        $sport = 'fudbal';

        return view('pages.show', [
            'sport' => $sport,
            'accent' => Accent::classes($sport),
            'active' => 'page',
            'page' => $page,
        ]);
    }
}
