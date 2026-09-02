<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Liquidacion extends Model
{
    use HasFactory;

    protected $table = 'liquidaciones';

    protected $fillable = [
        'empleado_id',
        'fecha_salida',
        'motivo',
        'anios_servicio',
        'dias_servicio',
        'salario_diario',
        'salario_diario_topado',
        'indemnizacion',
        'prestacion_renuncia',
        'vacacion_proporcional',
        'aguinaldo_proporcional',
        'salarios_pendientes',
        'otras_deducciones',
        'total_a_pagar',
        'observaciones',
        'generada_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_salida' => 'date',
            'anios_servicio' => 'integer',
            'dias_servicio' => 'integer',
            'salario_diario' => 'decimal:4',
            'salario_diario_topado' => 'decimal:4',
            'indemnizacion' => 'decimal:2',
            'prestacion_renuncia' => 'decimal:2',
            'vacacion_proporcional' => 'decimal:2',
            'aguinaldo_proporcional' => 'decimal:2',
            'salarios_pendientes' => 'decimal:2',
            'otras_deducciones' => 'decimal:2',
            'total_a_pagar' => 'decimal:2',
        ];
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    public function generador()
    {
        return $this->belongsTo(User::class, 'generada_por');
    }

    protected function motivoLegible(): Attribute
    {
        return Attribute::get(
            fn () => config("prestaciones.liquidacion.motivos.{$this->motivo}", $this->motivo)
        );
    }
}
