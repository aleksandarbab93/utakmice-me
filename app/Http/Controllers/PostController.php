<?php

namespace App\Http\Controllers;

use App\Support\Accent;
use App\Support\SampleData;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $post = SampleData::postBySlug($slug);

        abort_if($post === null, 404);

        $sport = in_array($post['league'], Accent::leagues('kosarka'), true) ? 'kosarka' : 'fudbal';

        $related = collect(SampleData::posts($sport))
            ->reject(fn ($p) => $p['slug'] === $post['slug'])
            ->take(2)
            ->values();

        return view('posts.show', [
            'sport' => $sport,
            'accent' => Accent::classes($sport),
            'post' => $post,
            'related' => $related,
        ]);
    }
}
