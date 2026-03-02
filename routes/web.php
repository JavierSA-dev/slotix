<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminHorarioController;
use App\Http\Controllers\Admin\AdminReservasController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MisReservasController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ReservaPublicaController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ─────────────────────────────────────────────────────────────────────────
// AUTENTICACIÓN CON RATE LIMITING
// ─────────────────────────────────────────────────────────────────────────
Route::middleware(['throttle:login'])->group(function () {
    Route::post('login', [LoginController::class, 'login'])->name('login.post');
});

Route::middleware(['throttle:password-reset'])->group(function () {
    Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');
});

Auth::routes(['verify' => true, 'register' => true]);

// ─────────────────────────────────────────────────────────────────────────
// RUTAS PÚBLICAS (sin autenticación)
// ─────────────────────────────────────────────────────────────────────────
Route::get('/', [ReservaPublicaController::class, 'index'])->name('reservas.public.index');
Route::get('/reservas/franjas', [ReservaPublicaController::class, 'franjas'])->name('reservas.franjas');
Route::post('/reservas', [ReservaPublicaController::class, 'store'])->name('reservas.store');
Route::get('/reservas/{token}', [ReservaPublicaController::class, 'show'])->name('reservas.show');
Route::patch('/reservas/{token}/cancelar', [ReservaPublicaController::class, 'cancelar'])->name('reservas.cancelar');

// ─────────────────────────────────────────────────────────────────────────
// RUTAS AUTENTICADAS
// ─────────────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/home', [HomeController::class, 'root'])->name('home');

    Route::post('/update-profile/{id}', [HomeController::class, 'updateProfile'])->name('updateProfile');
    Route::post('/update-password/{id}', [HomeController::class, 'updatePassword'])->name('updatePassword');

    Route::get('mantenimiento/maintenance', fn () => view('mantenimiento.maintenance'))->name('mantenimiento');
    Route::get('mantenimiento/comingsoon', fn () => view('mantenimiento.comingsoon'))->name('comingsoon');

    // ─────────────────────────────────────────────────────────────────────────
    // MIS RESERVAS (usuario autenticado)
    // ─────────────────────────────────────────────────────────────────────────
    Route::get('/mis-reservas', [MisReservasController::class, 'index'])->name('mis-reservas.index');
    Route::patch('/mis-reservas/{reserva}/cancelar', [MisReservasController::class, 'cancelar'])->name('mis-reservas.cancelar');

    // ─────────────────────────────────────────────────────────────────────────
    // PANEL ADMIN
    // ─────────────────────────────────────────────────────────────────────────
    Route::middleware(['es_admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::post('/mantenimiento/toggle', [AdminDashboardController::class, 'toggleMantenimiento'])->name('mantenimiento.toggle');
        Route::get('/horario', [AdminHorarioController::class, 'index'])->name('horario.index');
        Route::put('/horario', [AdminHorarioController::class, 'update'])->name('horario.update');
        Route::get('/reservas/get-ajax', [AdminReservasController::class, 'getAjax'])->name('reservas.getAjax');
        Route::get('/reservas/calendar-events', [AdminReservasController::class, 'calendarEvents'])->name('reservas.calendarEvents');
        Route::get('/reservas/buscar-usuarios', [AdminReservasController::class, 'buscarUsuarios'])->name('reservas.buscarUsuarios');
        Route::post('/reservas', [AdminReservasController::class, 'storeAdmin'])->name('reservas.store');
        Route::patch('/reservas/{reserva}/confirmar', [AdminReservasController::class, 'confirmar'])->name('reservas.confirmar');
        Route::patch('/reservas/{reserva}/cancelar-admin', [AdminReservasController::class, 'cancelarAdmin'])->name('reservas.cancelarAdmin');
        Route::get('/reservas', [AdminReservasController::class, 'index'])->name('reservas.index');
    });

    // ─────────────────────────────────────────────────────────────────────────
    // GESTIÓN DE USUARIOS
    // ─────────────────────────────────────────────────────────────────────────
    Route::middleware(['permission:users.index'])->group(function () {
        Route::get('users/export', [UserController::class, 'export'])->name('users.export');
        Route::get('users/get-ajax', [UserController::class, 'getAjax'])->name('users.getAjax');
        Route::resource('users', UserController::class);
    });

    // ─────────────────────────────────────────────────────────────────────────
    // SUPERADMIN
    // ─────────────────────────────────────────────────────────────────────────
    Route::middleware(['role:SuperAdmin'])->group(function () {
        Route::get('roles/get-ajax', [RoleController::class, 'getAjax'])->name('roles.getAjax');
        Route::get('roles/get-permissions/{id}', [RoleController::class, 'getPermissions'])->name('roles.getPermissions');
        Route::get('roles/roles-ajax', [RoleController::class, 'getRolesAjax'])->name('roles.rolesAjax');
        Route::resource('roles', RoleController::class);

        Route::get('permissions/get-ajax', [PermissionController::class, 'getAjax'])->name('permissions.getAjax');
        Route::resource('permissions', PermissionController::class);
    });
});
