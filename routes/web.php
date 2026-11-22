<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\MisRegistrosController;
use App\Http\Controllers\MisReportesController;
use App\Http\Controllers\RegistroHoraController;
use App\Http\Controllers\ResponsableController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ValorHoraController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'mostrarLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'iniciarSesion']);
});

// 'activo' saca al usuario si lo inactivaron mientras tenía la sesión abierta.
Route::middleware(['auth', 'activo'])->group(function () {
    Route::view('/', 'inicio')->name('inicio');
    Route::post('/logout', [AuthController::class, 'cerrarSesion'])->name('logout');

    Route::middleware('rol:administrador')->prefix('admin')->group(function () {
        // Gestión de usuarios: lista, crear, editar, eliminar y detalle (show).
        Route::resource('usuarios', UsuarioController::class)
            ->parameters(['usuarios' => 'usuario']);
        Route::patch('/usuarios/{usuario}/estado', [UsuarioController::class, 'cambiarEstado'])->name('usuarios.estado');
        Route::post('/usuarios/{usuario}/valores-hora', [ValorHoraController::class, 'store'])->name('valores-hora.store');

        // Registros de horas desde el detalle de usuario.
        Route::post('/usuarios/{usuario}/registros', [RegistroHoraController::class, 'store'])->name('registros.store');
        Route::resource('registros', RegistroHoraController::class)
            ->only(['edit', 'update', 'destroy'])
            ->parameters(['registros' => 'registro']);

        // Responsables e ítems: no se eliminan, se inactivan (por eso no hay destroy).
        Route::resource('responsables', ResponsableController::class)
            ->only(['index', 'store', 'edit', 'update'])
            ->parameters(['responsables' => 'responsable']);
        Route::patch('/responsables/{responsable}/estado', [ResponsableController::class, 'cambiarEstado'])->name('responsables.estado');

        Route::resource('items', ItemController::class)
            ->only(['index', 'store', 'edit', 'update'])
            ->parameters(['items' => 'item']);
        Route::patch('/items/{item}/estado', [ItemController::class, 'cambiarEstado'])->name('items.estado');
        Route::view('/reportes', 'proximamente', ['modulo' => 'Reportes'])->name('reportes.index');
    });

    Route::middleware('rol:dependiente')->group(function () {
        Route::get('/mis-registros', [MisRegistrosController::class, 'index'])->name('mis-registros.index');
        Route::post('/mis-registros', [MisRegistrosController::class, 'store'])->name('mis-registros.store');
        Route::get('/mis-reportes', [MisReportesController::class, 'index'])->name('mis-reportes.index');
    });
});
