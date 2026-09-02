<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Support\Accent;
use App\Support\PostFeed;
use App\Support\RelativeTime;
use App\Support\SampleData;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PostController extends Controller
{
    private const MONTHS = [
        1 => 'januar', 2 => 'februar', 3 => 'mart', 4 => 'april', 5 => 'maj', 6 => 'jun',
        7 => 'jul', 8 => 'avgust', 9 => 'septembar', 10 => 'oktobar', 11 => 'novembar', 12 => 'decembar',
    ];

    public function index(Request $request)
    {
        $sport = 'fudbal';
        $leagueSlug = $request->query('liga');
        $oldestFirst = $request->query('sort') === 'najstarije';
        $direction = $oldestFirst ? 'asc' : 'desc';

        $posts = PostFeed::reports($leagueSlug, $direction);
        $featured = (! $oldestFirst && $posts->currentPage() === 1) ? $posts->getCollection()->shift() : null;
        $featuredLabel = $featured ? self::dayLabel($featured['published_at']) : null;

        $groups = $posts->getCollection()
            ->groupBy(fn (array $post) => self::dayLabel($post['published_at']))
            ->map(fn ($items, $label) => ['label' => $label, 'posts' => $items->values()]);

        $latest = Post::where('type', 'izvestaj')->max('published_at');

        return view('posts.index', [
            'sport' => $sport,
            'accent' => Accent::classes($sport),
            'active' => 'vijesti',
            'posts' => $posts,
            'featured' => $featured,
            'featuredLabel' => $featuredLabel,
            'groups' => $groups,
            'leagues' => PostFeed::reportLeagues(),
            'activeLeague' => $leagueSlug,
            'oldestFirst' => $oldestFirst,
            'updatedLabel' => $latest ? RelativeTime::sr(Carbon::parse($latest)) : null,
            'description' => 'Najnovije vijesti iz fudbala — Premijer liga, La Liga, Serie A, Bundesliga i Liga šampiona. Rezultati i izvještaji sa utakmica, svakog dana.',
            'canonical' => $request->fullUrl(),
        ]);
    }

    private static function dayLabel(Carbon $date): string
    {
        if ($date->isToday()) {
            return 'DANAS';
        }

        if ($date->isYesterday()) {
            return 'JUČE';
        }

        $year = $date->year !== now()->year ? ' '.$date->year.'.' : '';

        return $date->day.'. '.self::MONTHS[$date->month].$year;
    }

    public function show(Request $request, string $slug)
    {
        $post = PostFeed::postBySlug($slug);
        $sport = 'fudbal';

        if ($post === null) {
            $post = SampleData::postBySlug($slug);
            abort_if($post === null, 404);
            $sport = in_array($post['league'], Accent::leagues('kosarka'), true) ? 'kosarka' : 'fudbal';
        }

        $related = $sport === 'fudbal'
            ? PostFeed::posts($sport)->reject(fn ($p) => $p['slug'] === $post['slug'])->take(2)->values()
            : collect(SampleData::posts($sport))->reject(fn ($p) => $p['slug'] === $post['slug'])->take(2)->values();

        return view('posts.show', [
            'sport' => $sport,
            'accent' => Accent::classes($sport),
            'post' => $post,
            'related' => $related,
        ]);
    }
}
