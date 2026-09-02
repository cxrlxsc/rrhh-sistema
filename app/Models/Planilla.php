<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Planilla extends Model
{
    use HasFactory;

    protected $fillable = [
        'empleado_id',
        'mes',
        'mes_numero',
        'anio',
        'salario_base',
        'bonificaciones',
        'minutos_extra',
        'total_devengado',
        'descuento_isss',
        'descuento_afp',
        'renta_base_imponible',
        'descuento_renta',
        'tramo_renta',
        'otras_deducciones',
        'total_deducciones',
        'salario_liquido',
        'aporte_patronal_isss',
        'aporte_patronal_afp',
        'costo_patronal',
        'dias_laborados',
        'llegadas_tardias',
        'generada_por',
    ];

    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'mes_numero' => 'integer',
            'salario_base' => 'decimal:2',
            'bonificaciones' => 'decimal:2',
            'minutos_extra' => 'integer',
            'total_devengado' => 'decimal:2',
            'descuento_isss' => 'decimal:2',
            'descuento_afp' => 'decimal:2',
            'renta_base_imponible' => 'decimal:2',
            'descuento_renta' => 'decimal:2',
            'otras_deducciones' => 'decimal:2',
            'total_deducciones' => 'decimal:2',
            'salario_liquido' => 'decimal:2',
            'aporte_patronal_isss' => 'decimal:2',
            'aporte_patronal_afp' => 'decimal:2',
            'costo_patronal' => 'decimal:2',
        ];
    }

    // Relación: Una planilla pertenece a un empleado
    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    // Auditoría: quién ejecutó la generación de esta planilla
    public function generador()
    {
        return $this->belongsTo(User::class, 'generada_por');
    }

    protected function periodo(): Attribute
    {
        return Attribute::get(fn () => "{$this->mes} {$this->anio}");
    }

    public function scopeDelPeriodo(Builder $query, int $anio, int $mes): Builder
    {
        return $query->where('anio', $anio)->where('mes_numero', $mes);
    }

    public function scopeDelEmpleado(Builder $query, int $empleadoId): Builder
    {
        return $query->where('empleado_id', $empleadoId);
    }

    /**
     * Orden cronológico real (no alfabético por nombre de mes).
     */
    public function scopeRecientesPrimero(Builder $query): Builder
    {
        return $query->orderByDesc('anio')->orderByDesc('mes_numero')->orderByDesc('id');
    }
}
