<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Roles del sistema (ver RolesYPermisosSeeder).
     */
    public const ROL_ADMIN = 'admin';
    public const ROL_RRHH = 'rrhh';
    public const ROL_EMPLEADO = 'empleado';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'empleado_id',
        'activo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    /**
     * Ficha de RRHH asociada a esta cuenta (null para usuarios administrativos
     * que no son empleados registrados en planilla).
     */
    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function esAdmin(): bool
    {
        return $this->hasRole(self::ROL_ADMIN);
    }

    /**
     * ¿Pertenece al equipo que administra el sistema (admin o RRHH)?
     */
    public function esPersonalRrhh(): bool
    {
        return $this->hasAnyRole([self::ROL_ADMIN, self::ROL_RRHH]);
    }
}
