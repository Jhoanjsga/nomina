@extends('layouts.app')

@section('titulo', 'Usuarios')

@section('contenido')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Usuarios</h1>
        <a href="{{ route('usuarios.create') }}" class="btn btn-primary">Crear usuario</a>
    </div>

    @include('parciales.filtro_estado', ['ruta' => 'usuarios.index'])

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Cédula</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th class="text-end">Valor hora</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($usuarios as $usuario)
                        @php($valorActual = $usuario->valorHoraActual())
                        <tr>
                            <td>{{ $usuario->nombre }}</td>
                            <td>{{ $usuario->cedula }}</td>
                            <td>{{ $usuario->correo }}</td>
                            <td>{{ ucfirst($usuario->rol) }}</td>
                            <td class="text-end">
                                @if ($valorActual)
                                    ${{ number_format($valorActual->valor, 0, ',', '.') }}
                                @elseif ($usuario->esDependiente())
                                    <span class="text-danger">Sin valor vigente</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($usuario->activo)
                                    <span class="badge bg-success">Activo</span>
                                @else
                                    <span class="badge bg-secondary">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('usuarios.edit', $usuario) }}" class="btn btn-sm btn-outline-primary">Editar</a>

                                @unless ($usuario->is(auth()->user()))
                                    <form method="POST" action="{{ route('usuarios.estado', $usuario) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-warning">
                                            {{ $usuario->activo ? 'Inactivar' : 'Activar' }}
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('usuarios.destroy', $usuario) }}" class="d-inline"
                                          onsubmit="return confirm('¿Eliminar este usuario? Esta acción no se puede deshacer.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No hay usuarios en este filtro.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
