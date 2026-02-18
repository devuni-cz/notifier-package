<?php

use Devuni\Notifier\Controllers\NotifierSendBackupController;
use Devuni\Notifier\Middleware\VerifyNotifierTokenMiddleware;
use Illuminate\Support\Facades\Route;

$prefix = config('notifier.route_prefix', 'api/notifier');

Route::post("{$prefix}/backup", NotifierSendBackupController::class)
    ->middleware([
        VerifyNotifierTokenMiddleware::class,
        'throttle:5,60',
    ]);
