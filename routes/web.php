<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'mostrarLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'iniciarSesion']);
});

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return view('inicio');
    })->name('inicio');

    Route::post('/logout', [AuthController::class, 'cerrarSesion'])->name('logout');
});