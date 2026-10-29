<?php

use App\Http\Controllers\Portal\PortalAuthController;
use App\Http\Controllers\Portal\PortalCalendarController;
use App\Http\Controllers\Portal\PortalHomeController;
use App\Http\Controllers\Portal\PortalPasswordController;
use App\Http\Controllers\Portal\PortalProgressController;
use App\Http\Controllers\Portal\PortalProfileController;
use App\Http\Controllers\Portal\PortalPushController;
use App\Http\Controllers\Portal\PortalSummaryController;
use App\Http\Middleware\EnsurePortalPasswordIsCurrent;
use Illuminate\Support\Facades\Route;

Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/login', [PortalAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [PortalAuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [PortalAuthController::class, 'logout'])->name('logout');

    Route::get('/olvide-password', [PortalPasswordController::class, 'showForgot'])->name('password.forgot');
    Route::post('/olvide-password', [PortalPasswordController::class, 'sendResetLink'])->name('password.email');
    Route::get('/restablecer-password', [PortalPasswordController::class, 'showReset'])->name('password.reset');
    Route::post('/restablecer-password', [PortalPasswordController::class, 'reset'])->name('password.update');

    Route::middleware(['auth:patient,caregiver', EnsurePortalPasswordIsCurrent::class])->group(function () {
        Route::get('/cambiar-contrasena', [PortalPasswordController::class, 'showForceChange'])->name('password.force-change');
        Route::post('/cambiar-contrasena', [PortalPasswordController::class, 'forceChange'])->name('password.force-change.submit');

        Route::get('/', [PortalHomeController::class, 'index'])->name('home');
        Route::post('/tour-visto', [PortalHomeController::class, 'dismissTour'])->name('tour.dismiss');
        Route::post('/modo-facil', [PortalHomeController::class, 'toggleEasyMode'])->name('easy-mode.toggle');
        Route::get('/diario', fn () => view('portal.diary'))->name('diary');
        Route::get('/metas', fn () => view('portal.goals'))->name('goals');
        Route::get('/bienestar', fn () => view('portal.wellbeing'))->name('wellbeing');
        Route::get('/pasaporte', fn () => view('portal.passport'))->name('passport');
        Route::get('/ayuda', fn () => view('portal.support'))->name('support');
        Route::get('/medicamentos', fn () => view('portal.medications'))->name('medications');
        Route::get('/monitoreo', fn () => view('portal.home-monitoring'))->name('home-monitoring');
        Route::get('/educacion', fn () => view('portal.education'))->name('education');
        Route::get('/preparacion-alta', fn () => view('portal.discharge-readiness'))->name('discharge-readiness');
        Route::get('/ruta-cuidador', fn () => view('portal.caregiver-journey'))->name('caregiver-journey');
        Route::get('/calendario', fn () => view('portal.calendar'))->name('calendar');
        Route::get('/calendario/eventos', [PortalCalendarController::class, 'events'])->name('calendar.events');
        Route::get('/resumen-cita', [PortalSummaryController::class, 'show'])->name('summary');
        Route::get('/progreso', [PortalProgressController::class, 'show'])->name('progress');
        Route::get('/perfil', [PortalProfileController::class, 'show'])->name('profile');
        Route::get('/notificaciones/vapid-key', [PortalPushController::class, 'publicKey'])->name('push.public-key');
        Route::post('/notificaciones/suscribir', [PortalPushController::class, 'subscribe'])->name('push.subscribe');
        Route::post('/notificaciones/desuscribir', [PortalPushController::class, 'unsubscribe'])->name('push.unsubscribe');
    });
});
