{{--
    Campos del formulario de registro de horas (dependiente y administrador).
    Recibe $responsables, $items, $registro (null al crear) y $fechasPermitidas:
    - con $fechasPermitidas (dependiente): la fecha es una lista con los días que puede elegir.
    - sin $fechasPermitidas (administrador): la fecha es libre; el servidor valida que sea día hábil.
--}}
<div class="row g-3">
    <div class="col-md-4">
        <label for="fecha" class="form-label">Fecha</label>
        @if ($fechasPermitidas)
            @php($fechaElegida = old('fecha', in_array(today()->toDateString(), $fechasPermitidas) ? today()->toDateString() : null))
            <select id="fecha" name="fecha" required class="form-select @error('fecha') is-invalid @enderror">
                @foreach ($fechasPermitidas as $fecha)
                    @php($dia = \Carbon\Carbon::parse($fecha))
                    <option value="{{ $fecha }}" @selected($fechaElegida === $fecha)>
                        {{ ucfirst($dia->locale('es')->isoFormat('dddd D [de] MMMM')) }}{{ $dia->isToday() ? ' (hoy)' : '' }}
                    </option>
                @endforeach
            </select>
        @else
            <input type="date" id="fecha" name="fecha" required
                   class="form-control @error('fecha') is-invalid @enderror"
                   value="{{ old('fecha', $registro?->fecha->format('Y-m-d') ?? today()->format('Y-m-d')) }}">
        @endif
        @error('fecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="hora_inicio" class="form-label">Hora de inicio</label>
        <input type="time" id="hora_inicio" name="hora_inicio" required min="08:00" max="18:00"
               class="form-control @error('hora_inicio') is-invalid @enderror"
               value="{{ old('hora_inicio', $registro ? substr($registro->hora_inicio, 0, 5) : null) }}">
        @error('hora_inicio') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="hora_fin" class="form-label">Hora de fin</label>
        <input type="time" id="hora_fin" name="hora_fin" required min="08:00" max="18:00"
               class="form-control @error('hora_fin') is-invalid @enderror"
               value="{{ old('hora_fin', $registro ? substr($registro->hora_fin, 0, 5) : null) }}">
        @error('hora_fin') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="responsable_id" class="form-label">Responsable</label>
        <select id="responsable_id" name="responsable_id" required
                class="form-select @error('responsable_id') is-invalid @enderror">
            <option value="">Selecciona…</option>
            @foreach ($responsables as $responsable)
                <option value="{{ $responsable->id }}" @selected(old('responsable_id', $registro?->responsable_id) == $responsable->id)>
                    {{ $responsable->nombre }}{{ $responsable->activo ? '' : ' (inactivo)' }}
                </option>
            @endforeach
        </select>
        @error('responsable_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="item_id" class="form-label">Ítem</label>
        <select id="item_id" name="item_id" required
                class="form-select @error('item_id') is-invalid @enderror">
            <option value="">Selecciona…</option>
            @foreach ($items as $item)
                <option value="{{ $item->id }}" @selected(old('item_id', $registro?->item_id) == $item->id)>
                    {{ $item->nombre }}{{ $item->activo ? '' : ' (inactivo)' }}
                </option>
            @endforeach
        </select>
        @error('item_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="radicado" class="form-label">Radicado</label>
        <input type="text" id="radicado" name="radicado" required maxlength="100"
               class="form-control @error('radicado') is-invalid @enderror"
               value="{{ old('radicado', $registro?->radicado) }}">
        @error('radicado') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="cliente" class="form-label">Cliente</label>
        <input type="text" id="cliente" name="cliente" required maxlength="100"
               class="form-control @error('cliente') is-invalid @enderror"
               value="{{ old('cliente', $registro?->cliente) }}">
        @error('cliente') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label for="observacion" class="form-label">Observación <span class="text-muted">(opcional)</span></label>
        <textarea id="observacion" name="observacion" rows="2" maxlength="255"
                  class="form-control @error('observacion') is-invalid @enderror">{{ old('observacion', $registro?->observacion) }}</textarea>
        @error('observacion') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
