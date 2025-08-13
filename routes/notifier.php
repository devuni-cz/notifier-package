<?php

use Devuni\Notifier\Controllers\NotifierController;
use Illuminate\Support\Facades\Route;

Route::get('/api/backup', NotifierController::class)->middleware('auth.backup');
