<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombres',
        'apellidos',
        'dui',
        'fecha_nacimiento',
        'fecha_contratacion',
        'salario_base',
        'departamento_id',
        'activo'
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'fecha_contratacion' => 'date',
            'salario_base' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    // Relación: Un empleado pertenece a un departamento
    public function departamento()
    {
        return $this->belongsTo(Departamento::class);
    }
    // Relación: Un empleado tiene muchas planillas (pagos)
    public function planillas()
    {
        return $this->hasMany(Planilla::class);
    }
    // Relación: Un empleado tiene muchas asistencias
    public function asistencias()
    {
        return $this->hasMany(Asistencia::class);
    }

    // Relación: Un empleado puede tener una cuenta de acceso al sistema
    public function user()
    {
        return $this->hasOne(User::class);
    }

    /**
     * Normaliza el DUI a 00000000-0 sin importar cómo venga del escáner.
     */
    public static function normalizarDui(string $dui): string
    {
        $soloDigitos = preg_replace('/\D/', '', $dui) ?? '';

        if (strlen($soloDigitos) !== 9) {
            return trim($dui);
        }

        return substr($soloDigitos, 0, 8).'-'.substr($soloDigitos, 8, 1);
    }

    protected function nombreCompleto(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->nombres} {$this->apellidos}"));
    }

    /**
     * Antigüedad en años cumplidos (útil para vacaciones e indemnización).
     */
    protected function aniosDeServicio(): Attribute
    {
        return Attribute::get(fn () => $this->fecha_contratacion?->diffInYears(now()) ?? 0);
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeBuscar(Builder $query, ?string $termino): Builder
    {
        if (blank($termino)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($termino) {
            $q->where('nombres', 'like', "%{$termino}%")
              ->orWhere('apellidos', 'like', "%{$termino}%")
              ->orWhere('dui', 'like', "%{$termino}%");
        });
    }
}
