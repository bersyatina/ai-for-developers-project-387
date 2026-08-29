<?php

use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\EventTypeController;
use App\Http\Controllers\Api\SlotController;
use Illuminate\Support\Facades\Route;

// Публичные эндпоинты без авторизации: гость и владелец (предустановленный профиль).

Route::get('/event-types', [EventTypeController::class, 'index']);
Route::post('/event-types', [EventTypeController::class, 'store']);

Route::middleware('throttle:slots')
    ->get('/event-types/{eventType}/slots', [SlotController::class, 'index']);

Route::get('/bookings', [BookingController::class, 'index']);
Route::middleware('throttle:bookings')
    ->post('/bookings', [BookingController::class, 'store']);
