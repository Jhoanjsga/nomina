<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'mostrarLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'iniciarSesion']);
});

Route::middleware('auth')->group(function () {
    Route::view('/', 'inicio')->name('inicio');
    Route::post('/logout', [AuthController::class, 'cerrarSesion'])->name('logout');

    Route::middleware('rol:administrador')->prefix('admin')->group(function () {
        Route::view('/usuarios', 'proximamente', ['modulo' => 'Usuarios'])->name('usuarios.index');
        Route::view('/responsables', 'proximamente', ['modulo' => 'Responsables'])->name('responsables.index');
        Route::view('/items', 'proximamente', ['modulo' => 'Ítems'])->name('items.index');
        Route::view('/registros', 'proximamente', ['modulo' => 'Registros activos'])->name('registros.index');
        Route::view('/finalizar', 'proximamente', ['modulo' => 'Finalizar horas'])->name('liquidaciones.index');
        Route::view('/reportes', 'proximamente', ['modulo' => 'Reportes'])->name('reportes.index');
    });

    Route::middleware('rol:dependiente')->group(function () {
        Route::view('/mis-registros', 'proximamente', ['modulo' => 'Mis horas activas'])->name('mis-registros.index');
        Route::view('/mis-reportes', 'proximamente', ['modulo' => 'Mis reportes'])->name('mis-reportes.index');
    });
});