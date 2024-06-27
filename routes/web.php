<?php

use App\Http\Controllers\TelegramBot;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
