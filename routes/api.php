<?php

use App\Http\Controllers\TelegramBot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::controller(TelegramBot::class)->group(function () {
    Route::post('/telegram/bot', 'botController');
});
