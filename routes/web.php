<?php

use App\Http\Controllers\Auth\{LoginController, RegisterController, ForgotPasswordController, ResetPasswordController};
use App\Http\Controllers\{HomeController, PermissionController, RoleController, UserController};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ─────────────────────────────────────────────────────────────────────────
// RUTAS DE AUTENTICACIÓN CON RATE LIMITING
// ─────────────────────────────────────────────────────────────────────────

// Login: 5 intentos por minuto (previene fuerza bruta)
Route::middleware(['throttle:login'])->group(function () {
    Route::post('login', [LoginController::class, 'login'])->name('login.post');
});

// Registro: 3 por hora (previene creación masiva de cuentas)
// POR DEFECTO LA HE COMENTADO PORQUE NO SOLEMOS HACER APPS CON REGISTRO PÚBLICO
// Route::middleware(['throttle:register'])->group(function () {
//     Route::post('register', [RegisterController::class, 'register'])->name('register.post');
// });

// Reset de contraseña: 3 por hora (previene abuso)
Route::middleware(['throttle:password-reset'])->group(function () {
    Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');
});

// Resto de rutas de autenticación (vistas GET, logout, etc.) - sin rate limiting agresivo
Auth::routes(['verify' => true]);

// Agrupamos todas las rutas que requieren autenticación
Route::middleware(['auth', 'active'])->group(function () {

    // ─────────────────────────────────────────────────────────────────────────
    // RUTAS PUBLICAS (cualquier usuario autenticado)
    // ─────────────────────────────────────────────────────────────────────────
    Route::get('/', [HomeController::class, 'root'])->name('root');
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // Perfil del usuario
    Route::post('/update-profile/{id}', [HomeController::class, 'updateProfile'])->name('updateProfile');
    Route::post('/update-password/{id}', [HomeController::class, 'updatePassword'])->name('updatePassword');

    Route::get('index/{locale}', [HomeController::class, 'lang']);

    // Páginas del sistema
    Route::get('mantenimiento/maintenance', fn() => view('mantenimiento.maintenance'))->name('mantenimiento');
    Route::get('mantenimiento/comingsoon', fn() => view('mantenimiento.comingsoon'))->name('comingsoon');

    // ─────────────────────────────────────────────────────────────────────────
    // RUTAS ADMIN 
    // ─────────────────────────────────────────────────────────────────────────
    Route::middleware(['permission:users.index'])->group(function () {
        Route::get('users/export', [UserController::class, 'export'])->name('users.export');
        Route::get('users/get-ajax', [UserController::class, 'getAjax'])->name('users.getAjax');
        Route::resource('users', UserController::class);
    });

    // ─────────────────────────────────────────────────────────────────────────
    // RUTAS SUPERADMIN
    // ─────────────────────────────────────────────────────────────────────────
    Route::middleware(['role:SuperAdmin'])->group(function () {
        // Roles
        Route::get('roles/get-ajax', [RoleController::class, 'getAjax'])->name('roles.getAjax');
        Route::get('roles/get-permissions/{id}', [RoleController::class, 'getPermissions'])->name('roles.getPermissions');
        Route::get('roles/roles-ajax', [RoleController::class, 'getRolesAjax'])->name('roles.rolesAjax');
        Route::resource('roles', RoleController::class);

        // Permisos
        Route::get('permissions/get-ajax', [PermissionController::class, 'getAjax'])->name('permissions.getAjax');
        Route::resource('permissions', PermissionController::class);
    });
});

Auth::routes();
