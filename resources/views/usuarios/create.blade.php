@extends('layouts.app')

@section('titulo', 'Crear usuario')

@section('contenido')
    <h1 class="h3 mb-3">Crear usuario</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('usuarios.store') }}">
                @csrf

                @include('usuarios._formulario', ['usuario' => null])

                <hr class="my-4">
                <h2 class="h5">Valor de la hora <span class="text-muted fs-6">(solo para dependientes)</span></h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="valor_hora" class="form-label">Valor de la hora</label>
                        <input type="number" id="valor_hora" name="valor_hora" step="0.01"
                               min="{{ config('nomina.valor_hora_minimo') }}"
                               class="form-control @error('valor_hora') is-invalid @enderror"
                               value="{{ old('valor_hora') }}">
                        <div class="form-text">
                            Mínimo pactado: ${{ number_format(config('nomina.valor_hora_minimo'), 0, ',', '.') }}
                        </div>
                        @error('valor_hora') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="vigente_desde" class="form-label">Vigente desde</label>
                        <input type="date" id="vigente_desde" name="vigente_desde"
                               class="form-control @error('vigente_desde') is-invalid @enderror"
                               value="{{ old('vigente_desde', today()->format('Y-m-d')) }}">
                        @error('vigente_desde') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
