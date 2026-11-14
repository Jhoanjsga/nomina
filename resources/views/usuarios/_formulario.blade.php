{{-- Campos comunes a crear y editar. $usuario es null al crear. --}}
<div class="row g-3">
    <div class="col-md-6">
        <label for="nombre" class="form-label">Nombre</label>
        <input type="text" id="nombre" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
               value="{{ old('nombre', $usuario?->nombre) }}" required maxlength="100">
        @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="cedula" class="form-label">Cédula</label>
        <input type="text" id="cedula" name="cedula" class="form-control @error('cedula') is-invalid @enderror"
               value="{{ old('cedula', $usuario?->cedula) }}" required maxlength="45">
        @error('cedula') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="correo" class="form-label">Correo</label>
        <input type="email" id="correo" name="correo" class="form-control @error('correo') is-invalid @enderror"
               value="{{ old('correo', $usuario?->correo) }}" required maxlength="100">
        @error('correo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="telefono" class="form-label">Teléfono</label>
        <input type="text" id="telefono" name="telefono" class="form-control @error('telefono') is-invalid @enderror"
               value="{{ old('telefono', $usuario?->telefono) }}" required maxlength="45">
        @error('telefono') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="direccion" class="form-label">Dirección <span class="text-muted">(opcional)</span></label>
        <input type="text" id="direccion" name="direccion" class="form-control @error('direccion') is-invalid @enderror"
               value="{{ old('direccion', $usuario?->direccion) }}" maxlength="100">
        @error('direccion') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="fecha_nacimiento" class="form-label">Fecha de nacimiento <span class="text-muted">(opcional)</span></label>
        <input type="date" id="fecha_nacimiento" name="fecha_nacimiento"
               class="form-control @error('fecha_nacimiento') is-invalid @enderror"
               value="{{ old('fecha_nacimiento', $usuario?->fecha_nacimiento?->format('Y-m-d')) }}">
        @error('fecha_nacimiento') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="rol" class="form-label">Rol</label>
        <select id="rol" name="rol" class="form-select @error('rol') is-invalid @enderror" required>
            @php($rolActual = old('rol', $usuario?->rol ?? 'dependiente'))
            <option value="dependiente" @selected($rolActual === 'dependiente')>Dependiente</option>
            <option value="administrador" @selected($rolActual === 'administrador')>Administrador</option>
        </select>
        @error('rol') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="contrasena" class="form-label">
            Contraseña
            @if ($usuario) <span class="text-muted">(vacía = no cambiar)</span> @endif
        </label>
        <input type="password" id="contrasena" name="contrasena"
               class="form-control @error('contrasena') is-invalid @enderror" @unless ($usuario) required @endunless minlength="8">
        @error('contrasena') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="contrasena_confirmation" class="form-label">Confirmar contraseña</label>
        <input type="password" id="contrasena_confirmation" name="contrasena_confirmation" class="form-control"
               @unless ($usuario) required @endunless minlength="8">
    </div>
</div>
