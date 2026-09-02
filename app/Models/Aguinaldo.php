<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aguinaldo extends Model
{
    use HasFactory;

    protected $fillable = [
        'empleado_id',
        'anio',
        'anios_servicio',
        'dias_aplicados',
        'salario_diario',
        'proporcional',
        'monto_bruto',
        'monto_exento',
        'monto_gravado',
        'descuento_renta',
        'monto_neto',
        'generado_por',
    ];

    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'anios_servicio' => 'integer',
            'dias_aplicados' => 'decimal:2',
            'proporcional' => 'boolean',
            'salario_diario' => 'decimal:4',
            'monto_bruto' => 'decimal:2',
            'monto_exento' => 'decimal:2',
            'monto_gravado' => 'decimal:2',
            'descuento_renta' => 'decimal:2',
            'monto_neto' => 'decimal:2',
        ];
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    public function generador()
    {
        return $this->belongsTo(User::class, 'generado_por');
    }

    public function scopeDelAnio(Builder $query, int $anio): Builder
    {
        return $query->where('anio', $anio);
    }
}
