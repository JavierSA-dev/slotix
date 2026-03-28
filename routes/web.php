<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminDiasCerradosController;
use App\Http\Controllers\Admin\AdminHorarioController;
use App\Http\Controllers\Admin\AdminNotificacionController;
use App\Http\Controllers\Admin\AdminReservasController;
use App\Http\Controllers\Admin\DemoController;
use App\Http\Controllers\Admin\EmpresaController;
use App\Http\Controllers\Admin\EmpresaModuloController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\MisReservasController;
use App\Http\Controllers\PerfilController;
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
Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return app(InicioController::class)->index();
})->name('inicio');

Route::middleware(['auth', 'active'])->post('/seleccionar-empresa', [InicioController::class, 'seleccionarEmpresa'])->name('inicio.seleccionar');

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
    Route::get('/mi-perfil', [PerfilController::class, 'show'])->name('mi-perfil');
    Route::get('/mis-reservas', [MisReservasController::class, 'index'])->name('mis-reservas.index');
    Route::patch('/mis-reservas/{reserva}/cancelar', [MisReservasController::class, 'cancelar'])->name('mis-reservas.cancelar');

    // ─────────────────────────────────────────────────────────────────────────
    // PANEL ADMIN
    // ─────────────────────────────────────────────────────────────────────────
    Route::middleware(['es_admin', 'empresa.required', 'empresa'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::post('/mantenimiento/toggle', [AdminDashboardController::class, 'toggleMantenimiento'])->name('mantenimiento.toggle');
        Route::get('/horario', [AdminHorarioController::class, 'index'])->name('horario.index');
        Route::put('/horario', [AdminHorarioController::class, 'update'])->name('horario.update');
        Route::post('/dias-cerrados', [AdminDiasCerradosController::class, 'store'])->name('dias-cerrados.store');
        Route::delete('/dias-cerrados/{diaCerrado}', [AdminDiasCerradosController::class, 'destroy'])->name('dias-cerrados.destroy');
        Route::get('/reservas/get-ajax', [AdminReservasController::class, 'getAjax'])->name('reservas.getAjax');
        Route::get('/reservas/calendar-events', [AdminReservasController::class, 'calendarEvents'])->name('reservas.calendarEvents');
        Route::get('/reservas/buscar-usuarios', [AdminReservasController::class, 'buscarUsuarios'])->name('reservas.buscarUsuarios');
        Route::post('/reservas', [AdminReservasController::class, 'storeAdmin'])->name('reservas.store');
        Route::get('/reservas/{reserva}', [AdminReservasController::class, 'show'])->name('reservas.show');
        Route::patch('/reservas/{reserva}', [AdminReservasController::class, 'update'])->name('reservas.update');
        Route::patch('/reservas/{reserva}/confirmar', [AdminReservasController::class, 'confirmar'])->name('reservas.confirmar');
        Route::patch('/reservas/{reserva}/cancelar-admin', [AdminReservasController::class, 'cancelarAdmin'])->name('reservas.cancelarAdmin');
        Route::get('/reservas', [AdminReservasController::class, 'index'])->name('reservas.index');

        // ─────────────────────────────────────────────────────────────────────
        // LISTADO SIMPLE DE EMPRESAS (para filtros y selects)
        // ─────────────────────────────────────────────────────────────────────
        Route::get('/empresas/list', [EmpresaController::class, 'listAjax'])->name('empresas.listAjax');

        // ─────────────────────────────────────────────────────────────────────
        // MI EMPRESA (Admin puede editar su propia empresa)
        // ─────────────────────────────────────────────────────────────────────
        Route::get('/mi-empresa', [EmpresaController::class, 'miEmpresa'])->name('mi-empresa.index');
        Route::put('/mi-empresa', [EmpresaController::class, 'actualizarMiEmpresa'])->name('mi-empresa.update');

        // ─────────────────────────────────────────────────────────────────────
        // NOTIFICACIONES ADMIN
        // ─────────────────────────────────────────────────────────────────────
        Route::get('/notificaciones', [AdminNotificacionController::class, 'index'])->name('notificaciones.index');
        Route::patch('/notificaciones/{id}/leida', [AdminNotificacionController::class, 'marcarLeida'])->name('notificaciones.leida');
        Route::patch('/notificaciones/todas-leidas', [AdminNotificacionController::class, 'marcarTodasLeidas'])->name('notificaciones.todasLeidas');

        // ─────────────────────────────────────────────────────────────────────
        // CAMBIO DE EMPRESA (disponible para admins con múltiples empresas)
        // ─────────────────────────────────────────────────────────────────────
        Route::post('/switch-empresa', [EmpresaController::class, 'switchEmpresa'])->name('switch.empresa');

        // ─────────────────────────────────────────────────────────────────────
        // GESTIÓN DE EMPRESAS (solo SuperAdmin)
        // ─────────────────────────────────────────────────────────────────────
        Route::middleware(['role:SuperAdmin'])->group(function () {
            Route::get('/demos', [DemoController::class, 'index'])->name('demos.index');
            Route::post('/demos', [DemoController::class, 'store'])->name('demos.store');
            Route::delete('/demos/{tenantId}', [DemoController::class, 'destroy'])->name('demos.destroy');

            Route::get('/empresas/get-ajax', [EmpresaController::class, 'getAjax'])->name('empresas.getAjax');
            Route::get('/empresas/{empresa}', [EmpresaController::class, 'show'])->name('empresas.show');
            Route::post('/empresas', [EmpresaController::class, 'store'])->name('empresas.store');
            Route::put('/empresas/{empresa}', [EmpresaController::class, 'update'])->name('empresas.update');
            Route::delete('/empresas/{empresa}', [EmpresaController::class, 'destroy'])->name('empresas.destroy');
            Route::get('/empresas', [EmpresaController::class, 'index'])->name('empresas.index');
            Route::post('/empresas/migrar-todas', [EmpresaController::class, 'migrarTodas'])->name('empresas.migrarTodas');

            Route::post('/empresas/{empresa}/modulos/{modulo}/toggle', [EmpresaModuloController::class, 'toggle'])->name('empresas.modulos.toggle');
        });
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

// ─────────────────────────────────────────────────────────────────────────
// RUTAS PÚBLICAS POR EMPRESA — al final para no interferir con otras rutas
// ─────────────────────────────────────────────────────────────────────────
Route::prefix('{empresa}')->middleware('tenancia.publica')->group(function () {
    Route::get('/demo-acceder/{tipo}', [ReservaPublicaController::class, 'demoAcceder'])
        ->name('demo.acceder')
        ->where('tipo', 'admin|usuario');
    Route::get('/', [ReservaPublicaController::class, 'index'])->name('reservas.public.index');
    Route::get('/franjas', [ReservaPublicaController::class, 'franjas'])->name('reservas.franjas');
    Route::post('/reservas', [ReservaPublicaController::class, 'store'])->name('reservas.store');
    Route::get('/reservas/{token}', [ReservaPublicaController::class, 'show'])->name('reservas.show');
    Route::patch('/reservas/{token}/cancelar', [ReservaPublicaController::class, 'cancelar'])->name('reservas.cancelar');
});
