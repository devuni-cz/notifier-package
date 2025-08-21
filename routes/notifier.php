<?php

use Illuminate\Support\Facades\Route;
use Devuni\Notifier\Controllers\NotifierController;

Route::get('/api/backup', NotifierController::class)
    ->middleware('throttle:5,1');
