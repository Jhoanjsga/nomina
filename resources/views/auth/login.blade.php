@extends('layouts.app')

@section('titulo', 'Iniciar sesión')

@section('contenido')
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm mt-5">
                <div class="card-body p-4">
                    <h1 class="h4 mb-4 text-center">Iniciar sesión</h1>

                    @error('correo')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror

                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="correo" class="form-label">Correo</label>
                            <input type="email" id="correo" name="correo" class="form-control"
                                   value="{{ old('correo') }}" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label for="contrasena" class="form-label">Contraseña</label>
                            <input type="password" id="contrasena" name="contrasena" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Entrar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection