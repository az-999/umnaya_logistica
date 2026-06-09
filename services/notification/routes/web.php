<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status'      => 'ok',
        'service'     => config('app.name'),
        'environment' => app()->environment(),
        'version'     => config('app.version'),
    ]);
});
