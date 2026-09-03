<?php

use App\Http\Controllers\LeagueController;
use App\Http\Controllers\LeaguesController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PushController;
use App\Http\Controllers\SportController;
use App\Http\Controllers\StaticPageController;
use App\Http\Controllers\StreamsController;
use Illuminate\Support\Facades\Route;

// Fudbal lives at the site root — no /fudbal prefix.
Route::get('/', [SportController::class, 'home'])
    ->defaults('sport', 'fudbal')
    ->name('home.fudbal');

Route::get('/utakmice', [SportController::class, 'scores'])
    ->defaults('sport', 'fudbal')
    ->name('scores.fudbal');

Route::get('/tabele', [SportController::class, 'standings'])
    ->defaults('sport', 'fudbal')
    ->name('standings.fudbal');

// Košarka keeps its /kosarka prefix.
Route::get('/kosarka', [SportController::class, 'home'])
    ->defaults('sport', 'kosarka')
    ->name('home.kosarka');

Route::get('/kosarka/utakmice', [SportController::class, 'scores'])
    ->defaults('sport', 'kosarka')
    ->name('scores.kosarka');

Route::get('/kosarka/tabele', [SportController::class, 'standings'])
    ->defaults('sport', 'kosarka')
    ->name('standings.kosarka');

Route::get('/vijesti', [PostController::class, 'index'])
    ->name('post.index');

Route::get('/vijesti/{slug}', [PostController::class, 'show'])
    ->name('post.show');

Route::get('/mec/{fixture}', [MatchController::class, 'show'])
    ->name('match.show');

Route::get('/prenosi-uzivo', [StreamsController::class, 'index'])
    ->name('streams');

Route::get('/lige', [LeaguesController::class, 'index'])
    ->name('leagues');

Route::get('/liga/{slug}', [LeagueController::class, 'show'])
    ->name('league.show');

Route::get('/liga/{slug}/rezultati', [LeagueController::class, 'results'])
    ->name('league.results');

Route::get('/liga/{slug}/raspored', [LeagueController::class, 'fixtures'])
    ->name('league.fixtures');

Route::get('/o-nama', [StaticPageController::class, 'show'])
    ->defaults('slug', 'o-nama')
    ->name('page.about');

Route::get('/kontakt', [StaticPageController::class, 'show'])
    ->defaults('slug', 'kontakt')
    ->name('page.contact');

Route::get('/privatnost', [StaticPageController::class, 'show'])
    ->defaults('slug', 'privatnost')
    ->name('page.privacy');

Route::get('/oglasavanje', [StaticPageController::class, 'show'])
    ->defaults('slug', 'oglasavanje')
    ->name('page.advertising');

Route::get('/api/push/kljuc', [PushController::class, 'key'])
    ->name('push.key')
    ->middleware('throttle:30,1');

Route::post('/api/push/prijava', [PushController::class, 'subscribe'])
    ->name('push.subscribe')
    ->middleware('throttle:30,1');

Route::post('/api/push/odjava', [PushController::class, 'unsubscribe'])
    ->name('push.unsubscribe')
    ->middleware('throttle:30,1');

// Old URLs redirect to the new root-based scheme.
Route::redirect('/fudbal', '/');
Route::redirect('/fudbal/rezultati', '/utakmice');
Route::redirect('/fudbal/tabele', '/tabele');
Route::redirect('/rezultati', '/utakmice');
Route::redirect('/kosarka/rezultati', '/kosarka/utakmice');
Route::get('/vesti/{slug}', fn (string $slug) => redirect("/vijesti/{$slug}"));
