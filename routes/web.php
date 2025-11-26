<?php

use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\OAuthCallbackController;
use Illuminate\Support\Facades\Route;

Route::view('privacy-policy', 'privacy-policy')->name('privacy-policy');
Route::view('terms', 'terms')->name('terms');

// OAuth callback route (for TikTok, YouTube, Instagram)
Route::get('oauth2/{platform}/callback', [OAuthCallbackController::class, 'handle'])
    ->where('platform', 'youtube|tiktok|instagram')
    ->name('oauth.callback');

Route::get('login', [LoginController::class, 'index'])
    ->middleware('guest')
    ->name('login.view');
Route::post('login', [LoginController::class, 'login'])
    ->middleware('guest')
    ->name('login');

Route::middleware(['auth'])->name('admin.')->group(function () {

    Route::post('logout', [LoginController::class, 'logout'])->name('logout');
    // Dashboard
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    // Integrations OAuth
    Route::get('authorize/{platform}', IntegrationController::class)->name('authorize');

    // TODO: Scheduled Uploads Management - Controllers need to be implemented
    // Route::get('uploads', [UploadsController::class, 'index'])->name('uploads.index');
    // Route::post('uploads/{upload}/retry', [UploadsController::class, 'retry'])->name('uploads.retry');
    // Route::delete('uploads/{upload}', [UploadsController::class, 'destroy'])->name('uploads.delete');

    // TODO: Settings Management - Controllers need to be implemented
    // Route::get('settings', [SettingsController::class, 'edit'])->name('settings');
    // Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
});


