<?php

use App\Http\Controllers\MatchController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\SportController;
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

// Old URLs redirect to the new root-based scheme.
Route::redirect('/fudbal', '/');
Route::redirect('/fudbal/rezultati', '/utakmice');
Route::redirect('/fudbal/tabele', '/tabele');
Route::redirect('/rezultati', '/utakmice');
Route::redirect('/kosarka/rezultati', '/kosarka/utakmice');
Route::get('/vesti/{slug}', fn (string $slug) => redirect("/vijesti/{$slug}"));
