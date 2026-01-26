<?php

use Illuminate\Support\Facades\Route;
use Devuni\Notifier\Controllers\NotifierSendBackupController;
use Devuni\Notifier\Middleware\VerifyNotifierTokenMiddleware;

Route::post('/api/notifier/backup', NotifierSendBackupController::class)
    ->middleware([
        VerifyNotifierTokenMiddleware::class,
        'throttle:5,60'
    ]);
