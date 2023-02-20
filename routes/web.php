<?php

use Illuminate\Support\Facades\Route;

// Catch all route
Route::get('/{view?}', 'IndexController')
    ->where('view', '(.*)')
    ->name('log-viewer.index');
