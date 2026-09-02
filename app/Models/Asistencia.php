<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Asistencia extends Model
{
    use HasFactory;

    public const ESTADO_PUNTUAL = 'Puntual';
    public const ESTADO_TARDE = 'Llegada Tardía';

    protected $fillable = [
        'empleado_id',
        'fecha',
        'hora_entrada',
        'hora_salida',
        'ultimo_marcaje_at',
        'ip_marcaje',
        'estado_entrada',
        'minutos_tarde',
        'minutos_trabajados',
    ];

    protected function casts(): array
    {
        return [
            'ultimo_marcaje_at' => 'datetime',
            'minutos_tarde' => 'integer',
            'minutos_trabajados' => 'integer',
        ];
    }

    /**
     * 'fecha' se maneja a mano en lugar de con el cast 'date' a propósito:
     * el cast escribe "Y-m-d H:i:s" en la columna, y aunque MySQL lo trunca,
     * en SQLite deja la hora pegada y rompe los filtros por rango de fechas.
     * Al escribir se guarda solo la fecha; al leer se devuelve un Carbon.
     */
    protected function fecha(): Attribute
    {
        return Attribute::make(
            get: fn ($valor) => $valor ? Carbon::parse($valor)->startOfDay() : null,
            set: fn ($valor) => $valor ? Carbon::parse($valor)->toDateString() : null,
        );
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    /**
     * Jornada en formato legible: "8h 15m".
     */
    protected function jornada(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->minutos_trabajados) {
                return '—';
            }

            return intdiv($this->minutos_trabajados, 60).'h '.($this->minutos_trabajados % 60).'m';
        });
    }

    protected function llegoTarde(): Attribute
    {
        return Attribute::get(fn () => $this->estado_entrada === self::ESTADO_TARDE);
    }

    public function scopeHoy(Builder $query): Builder
    {
        return $query->whereDate('fecha', today());
    }

    public function scopeDelPeriodo(Builder $query, int $anio, int $mes): Builder
    {
        return $query->whereYear('fecha', $anio)->whereMonth('fecha', $mes);
    }

    public function scopeTardias(Builder $query): Builder
    {
        return $query->where('estado_entrada', self::ESTADO_TARDE);
    }
}
