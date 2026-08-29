<?php

use App\Http\Controllers\PostController;
use App\Http\Controllers\SportController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/fudbal');

Route::get('/{sport}', [SportController::class, 'home'])
    ->where('sport', 'fudbal|kosarka')
    ->name('home');

Route::get('/{sport}/rezultati', [SportController::class, 'scores'])
    ->where('sport', 'fudbal|kosarka')
    ->name('scores');

Route::get('/{sport}/tabele', [SportController::class, 'standings'])
    ->where('sport', 'fudbal|kosarka')
    ->name('standings');

Route::get('/vesti/{slug}', [PostController::class, 'show'])
    ->name('post.show');
