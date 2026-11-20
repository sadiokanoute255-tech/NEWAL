<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CheckInController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\GuestController;
use App\Http\Controllers\Api\GuestGroupController;
use App\Http\Controllers\Api\GuestImportController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\PublicInvitationController;
use App\Http\Controllers\Api\StatsController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

// Pages publiques de l'invité : aucun compte requis
Route::prefix('public/invitations')->middleware('throttle:60,1')->group(function () {
    Route::get('{token}', [PublicInvitationController::class, 'show']);
    Route::post('{token}/rsvp', [PublicInvitationController::class, 'rsvp']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    // Événements
    Route::apiResource('events', EventController::class);
    Route::post('events/{event}/publish', [EventController::class, 'publish']);
    Route::post('events/{event}/duplicate', [EventController::class, 'duplicate']);

    // Invités
    Route::get('events/{event}/guests/summary', [GuestController::class, 'summary']);
    Route::get('events/{event}/guests/template', [GuestImportController::class, 'template']);
    Route::post('events/{event}/guests/import', [GuestImportController::class, 'import']);
    Route::apiResource('events.guests', GuestController::class)->shallow();

    // Cercles d'invités
    Route::apiResource('events.groups', GuestGroupController::class)->shallow()->except('show');

    // Invitations
    Route::get('events/{event}/invitations', [InvitationController::class, 'index']);
    Route::post('events/{event}/invitations/generate', [InvitationController::class, 'generate']);
    Route::get('invitations/{invitation}/whatsapp', [InvitationController::class, 'whatsapp']);
    Route::post('invitations/{invitation}/mark-sent', [InvitationController::class, 'markSent']);

    // Tableau de bord (Phase 4)
    Route::get('events/{event}/stats', [StatsController::class, 'show']);

    // Contrôle d'accès (Phase 5)
    Route::post('events/{event}/checkin/lookup', [CheckInController::class, 'lookup']);
    Route::get('events/{event}/checkin/search', [CheckInController::class, 'search']);
    Route::post('events/{event}/checkin', [CheckInController::class, 'store']);
    Route::get('events/{event}/checkins', [CheckInController::class, 'index']);
    Route::post('checkins/{checkIn}/cancel', [CheckInController::class, 'cancel']);
});
