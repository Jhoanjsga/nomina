<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Nómina Savio')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    @auth
        <nav class="navbar navbar-dark bg-dark">
            <div class="container">
                <span class="navbar-brand">Nómina Savio</span>
                <form method="POST" action="{{ route('logout') }}" class="d-flex align-items-center">
                    @csrf
                    <span class="text-white me-3">{{ auth()->user()->nombre }}</span>
                    <button type="submit" class="btn btn-outline-light btn-sm">Cerrar sesión</button>
                </form>
            </div>
        </nav>
                <div class="bg-white border-bottom">
            <ul class="nav container">
                <li class="nav-item"><a class="nav-link" href="{{ route('inicio') }}">Inicio</a></li>
                @if (auth()->user()->esAdministrador())
                    <li class="nav-item"><a class="nav-link" href="{{ route('usuarios.index') }}">Usuarios</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('responsables.index') }}">Responsables</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('items.index') }}">Ítems</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('reportes.index') }}">Reportes</a></li>
                @else
                    <li class="nav-item"><a class="nav-link" href="{{ route('mis-registros.index') }}">Mis horas activas</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('mis-reportes.index') }}">Mis reportes</a></li>
                @endif
            </ul>
        </div>
    @endauth

    <main class="container py-4">
        {{-- Mensajes que envían los controladores con ->with('exito') o ->with('error') --}}
        @if (session('exito'))
            <div class="alert alert-success">{{ session('exito') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @yield('contenido')
    </main>
</body>
</html>