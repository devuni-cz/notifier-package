<?php

use Illuminate\Support\Facades\Route;
use Devuni\Notifier\Controllers\NotifierSendBackupController;

Route::get('/api/backup', NotifierSendBackupController::class)
    ->middleware('throttle:5,60');
