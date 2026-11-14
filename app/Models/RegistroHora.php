<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class RegistroHora extends Model
{
    protected $table = 'registros_horas';

    protected $fillable = [
        'usuario_id', 'responsable_id', 'item_id', 'liquidacion_id',
        'fecha', 'hora_inicio', 'hora_fin', 'radicado', 'cliente', 'observacion',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    // Las horas siempre se guardan como HH:MM:SS (formato TIME de MySQL),
    // aunque el formulario las envíe como HH:MM. Así las comparaciones de cruces
    // dan el mismo resultado en MySQL y en la base de pruebas.
    protected function horaInicio(): Attribute
    {
        return Attribute::make(set: fn ($valor) => Carbon::parse($valor)->format('H:i:s'));
    }

    protected function horaFin(): Attribute
    {
        return Attribute::make(set: fn ($valor) => Carbon::parse($valor)->format('H:i:s'));
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }

    public function responsable()
    {
        return $this->belongsTo(Responsable::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function liquidacion()
    {
        return $this->belongsTo(Liquidacion::class);
    }
}
