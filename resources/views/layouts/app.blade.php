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
    @endauth

    <main class="container py-4">
        @yield('contenido')
    </main>
</body>
</html>