<?php

namespace App\Models;

use Carbon\CarbonInterface;
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
        'activo',
        'fecha_salida',
        'motivo_salida',
        'nit',
        'numero_isss',
        'numero_afp',
        'afp_administradora',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'fecha_contratacion' => 'date',
            'fecha_salida' => 'date',
            'salario_base' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    /**
     * Salario diario legal: el salario mensual se divide siempre entre 30
     * (mes comercial), sin importar cuántos días tenga el mes real.
     * Es la base de aguinaldo, vacaciones, indemnización y horas extra.
     */
    public function salarioDiario(): float
    {
        return round((float) $this->salario_base / (int) config('nomina.dias_mes_comercial', 30), 4);
    }

    /**
     * Valor de la hora ordinaria (jornada diurna de 8 horas).
     */
    public function salarioHora(): float
    {
        $horasJornada = (int) config('prestaciones.horas_extra.jornada_ordinaria_minutos', 480) / 60;

        return round($this->salarioDiario() / max($horasJornada, 1), 4);
    }

    /**
     * Años completos de servicio a una fecha de corte (hoy por defecto).
     */
    public function antiguedadEnAnios(?CarbonInterface $corte = null): int
    {
        if (! $this->fecha_contratacion) {
            return 0;
        }

        return (int) $this->fecha_contratacion->diffInYears($corte ?? now());
    }

    /**
     * Días calendario laborados a una fecha de corte.
     */
    public function diasDeServicio(?CarbonInterface $corte = null): int
    {
        if (! $this->fecha_contratacion) {
            return 0;
        }

        return (int) $this->fecha_contratacion->diffInDays($corte ?? now());
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

    // Prestaciones laborales
    public function vacaciones()
    {
        return $this->hasMany(Vacacion::class);
    }

    public function aguinaldos()
    {
        return $this->hasMany(Aguinaldo::class);
    }

    public function liquidacion()
    {
        return $this->hasOne(Liquidacion::class);
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
