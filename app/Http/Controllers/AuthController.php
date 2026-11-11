<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function mostrarLogin()
    {
        return view('auth.login');
    }

    public function iniciarSesion(Request $request)
    {
        $datos = $request->validate([
            'correo' => ['required', 'email'],
            'contrasena' => ['required'],
        ]);

        $credenciales = [
            'correo' => $datos['correo'],
            'password' => $datos['contrasena'],
            'activo' => true,
        ];

        if (Auth::attempt($credenciales)) {
            $request->session()->regenerate();

            return redirect()->route('inicio');
        }

        return back()
            ->withErrors(['correo' => 'Correo o contraseña incorrectos, o usuario inactivo.'])
            ->onlyInput('correo');
    }

    public function cerrarSesion(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}