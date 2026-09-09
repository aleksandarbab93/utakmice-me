<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Page cache
    |--------------------------------------------------------------------------
    |
    | Whole rendered pages, held for half a minute — see
    | App\Http\Middleware\CachePage. Off in tests, where a cached response
    | would hide the queries a test is there to make.
    |
    */

    'enabled' => env('PAGE_CACHE', true),

];
