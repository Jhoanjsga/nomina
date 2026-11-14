@extends('layouts.app')

@section('titulo', 'Editar usuario')

@section('contenido')
    <h1 class="h3 mb-3">Editar usuario: {{ $usuario->nombre }}</h1>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('usuarios.update', $usuario) }}">
                @csrf
                @method('PUT')

                @include('usuarios._formulario', ['usuario' => $usuario])

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">Volver</a>
                </div>
            </form>
        </div>
    </div>

    @if ($usuario->esDependiente())
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5">Valor de la hora</h2>
                <p class="text-muted small">
                    El historial no se edita ni se borra. Para cambiar el valor, agrega uno nuevo con la fecha desde la que rige.
                    Cada registro de horas usa el último valor vigente a su fecha.
                </p>

                @if ($valoresHora->isEmpty())
                    <div class="alert alert-warning">Este dependiente no tiene valor de la hora asignado.</div>
                @else
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Vigente desde</th>
                                <th class="text-end">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($valoresHora as $valorHora)
                                <tr>
                                    <td>{{ $valorHora->vigente_desde->format('d/m/Y') }}</td>
                                    <td class="text-end">${{ number_format($valorHora->valor, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                <form method="POST" action="{{ route('valores-hora.store', $usuario) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-4">
                        <label for="valor" class="form-label">Nuevo valor</label>
                        <input type="number" id="valor" name="valor" step="0.01"
                               min="{{ config('nomina.valor_hora_minimo') }}"
                               class="form-control @error('valor') is-invalid @enderror"
                               value="{{ old('valor') }}" required>
                        @error('valor') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="vigente_desde" class="form-label">Vigente desde</label>
                        <input type="date" id="vigente_desde" name="vigente_desde"
                               class="form-control @error('vigente_desde') is-invalid @enderror"
                               value="{{ old('vigente_desde') }}" required>
                        @error('vigente_desde') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-success">Agregar valor</button>
                    </div>
                    <div class="col-12 form-text">
                        Mínimo pactado: ${{ number_format(config('nomina.valor_hora_minimo'), 0, ',', '.') }}
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
