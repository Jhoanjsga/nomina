<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerificarActivo
{
    // En cada petición revisa que el usuario con sesión siga activo.
    // Si el administrador lo inactivó mientras estaba conectado, lo saca de inmediato
    // en lugar de esperar a que la sesión venza.
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && ! $usuario->activo) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['correo' => 'Tu usuario fue inactivado. Comunícate con el administrador.']);
        }

        return $next($request);
    }
}
