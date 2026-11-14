{{-- Botones de filtro Activos / Inactivos / Todos. Recibe $ruta (nombre de la ruta) y $estado. --}}
<div class="btn-group mb-3">
    <a href="{{ route($ruta, ['estado' => 'activos']) }}"
       class="btn btn-outline-secondary {{ $estado === 'activos' ? 'active' : '' }}">Activos</a>
    <a href="{{ route($ruta, ['estado' => 'inactivos']) }}"
       class="btn btn-outline-secondary {{ $estado === 'inactivos' ? 'active' : '' }}">Inactivos</a>
    <a href="{{ route($ruta, ['estado' => 'todos']) }}"
       class="btn btn-outline-secondary {{ $estado === 'todos' ? 'active' : '' }}">Todos</a>
</div>
