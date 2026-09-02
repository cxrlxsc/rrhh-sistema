<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vacacion extends Model
{
    use HasFactory;

    // Laravel pluralizaría "Vacacion" como "vacacions".
    protected $table = 'vacaciones';

    public const ESTADO_PROGRAMADA = 'programada';
    public const ESTADO_GOZADA = 'gozada';
    public const ESTADO_CANCELADA = 'cancelada';

    protected $fillable = [
        'empleado_id',
        'fecha_inicio',
        'fecha_fin',
        'dias',
        'salario_diario',
        'monto_base',
        'recargo',
        'total_pagado',
        'estado',
        'observaciones',
        'registrada_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'dias' => 'integer',
            'salario_diario' => 'decimal:4',
            'monto_base' => 'decimal:2',
            'recargo' => 'decimal:2',
            'total_pagado' => 'decimal:2',
        ];
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    public function registrador()
    {
        return $this->belongsTo(User::class, 'registrada_por');
    }

    /**
     * Solo los períodos vigentes consumen saldo de días.
     */
    public function scopeVigentes(Builder $query): Builder
    {
        return $query->whereIn('estado', [self::ESTADO_PROGRAMADA, self::ESTADO_GOZADA]);
    }

    public function scopeDelAnio(Builder $query, int $anio): Builder
    {
        return $query->whereYear('fecha_inicio', $anio);
    }
}
