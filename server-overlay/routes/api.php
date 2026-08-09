<?php

use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Admin\AuditLogController;
use App\Http\Controllers\Api\Admin\EventController as AdminEventController;
use App\Http\Controllers\Api\Admin\OrganiserRequestController as AdminOrganiserRequestController;
use App\Http\Controllers\Api\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserModerationController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BlockController;
use App\Http\Controllers\Api\BookmarkController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\FriendController;
use App\Http\Controllers\Api\FriendRequestController;
use App\Http\Controllers\Api\MatchController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\OrganiserRequestController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UserProfileController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// Stricter than the general API baseline (5/min, keyed by email+IP) since
// these are exactly the endpoints a brute-force or credential-stuffing
// script would target — see AppServiceProvider's 'auth' RateLimiter.
Route::middleware('throttle:auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
    Route::post('/reset-password', [PasswordResetController::class, 'reset']);
});

// This app uses Sanctum token auth (not SPA cookie sessions), so the
// broadcasting auth route needs auth:sanctum instead of Laravel's default
// 'web' guard — otherwise Echo's private-channel subscriptions can never
// authenticate. Registered here (inside routes/api.php) so it lands at
// /api/broadcasting/auth alongside the rest of the API.
Broadcast::routes(['middleware' => ['auth:sanctum']]);

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/photo', [ProfileController::class, 'uploadPhoto']);
    Route::patch('/quiz', [QuizController::class, 'update']);

    Route::get('/users/me/events', [EventController::class, 'myEvents']);
    Route::get('/users/{user}', [UserProfileController::class, 'show']);

    Route::get('/events', [EventController::class, 'index']);
    Route::post('/events', [EventController::class, 'store']);
    Route::get('/events/{event}', [EventController::class, 'show']);
    Route::patch('/events/{event}', [EventController::class, 'update']);
    Route::delete('/events/{event}', [EventController::class, 'destroy']);
    Route::post('/events/{event}/cover-image', [EventController::class, 'uploadCoverImage']);
    Route::post('/events/{event}/register', [EventController::class, 'register']);
    Route::delete('/events/{event}/register', [EventController::class, 'cancelRegistration']);

    Route::get('/bookmarks', [BookmarkController::class, 'index']);
    Route::post('/bookmarks/{event}', [BookmarkController::class, 'store']);
    Route::delete('/bookmarks/{event}', [BookmarkController::class, 'destroy']);

    Route::get('/matches', [MatchController::class, 'index']);

    Route::post('/friends/requests', [FriendRequestController::class, 'store']);
    Route::get('/friends/requests/incoming', [FriendRequestController::class, 'incoming']);
    Route::get('/friends/requests/outgoing', [FriendRequestController::class, 'outgoing']);
    Route::patch('/friends/requests/{friendRequest}/accept', [FriendRequestController::class, 'accept']);
    Route::patch('/friends/requests/{friendRequest}/decline', [FriendRequestController::class, 'decline']);
    Route::get('/friends', [FriendController::class, 'index']);
    Route::delete('/friends/{user}', [FriendController::class, 'destroy']);

    Route::get('/blocks', [BlockController::class, 'index']);
    Route::post('/blocks/{user}', [BlockController::class, 'store']);
    Route::delete('/blocks/{user}', [BlockController::class, 'destroy']);

    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::post('/conversations', [ConversationController::class, 'store']);
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show']);
    Route::post('/conversations/{conversation}/read', [ConversationController::class, 'markRead']);
    Route::get('/conversations/{conversation}/messages', [MessageController::class, 'index']);
    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store']);

    Route::post('/reports', [ReportController::class, 'store'])->middleware('throttle:reports');
    Route::post('/organiser-requests', [OrganiserRequestController::class, 'store']);

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/reports', [AdminReportController::class, 'index']);
        Route::patch('/reports/{report}', [AdminReportController::class, 'update']);
        Route::get('/reports/{report}/context', [AdminReportController::class, 'context']);

        Route::get('/flagged-accounts', [AdminUserModerationController::class, 'flagged']);
        Route::post('/users/{user}/suspend', [AdminUserModerationController::class, 'suspend']);
        Route::post('/users/{user}/unsuspend', [AdminUserModerationController::class, 'unsuspend']);

        Route::get('/users', [UserManagementController::class, 'index']);
        Route::get('/users/{id}', [UserManagementController::class, 'show']);
        Route::patch('/users/{id}', [UserManagementController::class, 'update']);
        Route::delete('/users/{id}', [UserManagementController::class, 'destroy']);

        Route::patch('/events/{event}', [AdminEventController::class, 'update']);
        Route::delete('/events/{event}', [AdminEventController::class, 'destroy']);
        Route::get('/events/{event}/registrations', [AdminEventController::class, 'registrations']);
        Route::delete('/events/{event}/registrations/{registration}', [AdminEventController::class, 'removeRegistration']);

        Route::get('/organiser-requests', [AdminOrganiserRequestController::class, 'index']);
        Route::post('/organiser-requests/{user}/approve', [AdminOrganiserRequestController::class, 'approve']);
        Route::post('/organiser-requests/{user}/reject', [AdminOrganiserRequestController::class, 'reject']);

        Route::get('/audit-logs', [AuditLogController::class, 'index']);

        Route::get('/admins', [AdminUserController::class, 'index']);
        Route::post('/admins', [AdminUserController::class, 'store']);
        Route::patch('/admins/{user}', [AdminUserController::class, 'update']);
        Route::delete('/admins/{user}', [AdminUserController::class, 'destroy']);
    });
});
