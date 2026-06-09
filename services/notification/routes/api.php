<?php

use App\Http\Controllers\Api\BulkNotificationController;
use App\Http\Controllers\Api\DeliveryCallbackController;
use App\Http\Controllers\Api\SubscriberNotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('api.key')->group(function () {
    Route::post('/notifications/bulk', [BulkNotificationController::class, 'store']);
    Route::get('/subscribers/{subscriberId}/notifications', [SubscriberNotificationController::class, 'index']);
    Route::post('/notifications/{id}/delivery-callback', [DeliveryCallbackController::class, 'store']);
});
