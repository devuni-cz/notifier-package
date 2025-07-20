<?php

use Illuminate\Support\Facades\Route;
use Devuni\Notifier\Controllers\NotifierController;

Route::post('/api/backup', NotifierController::class);
