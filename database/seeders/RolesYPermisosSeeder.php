<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesYPermisosSeeder extends Seeder
{
    /**
     * Catálogo de permisos del sistema.
     * Se declara aquí para que agregar un módulo nuevo sea una sola línea.
     */
    public const PERMISOS = [
        'empleados.ver' => 'Consultar el directorio y la ficha de empleados',
        'empleados.gestionar' => 'Crear, editar y dar de baja empleados',
        'departamentos.gestionar' => 'Administrar los departamentos de la empresa',
        'planillas.ver' => 'Ver la planilla de todos los empleados',
        'planillas.generar' => 'Ejecutar el cálculo de nómina del período',
        'asistencias.ver' => 'Consultar el reporte de marcajes',
        'prestaciones.ver' => 'Consultar aguinaldos, vacaciones y liquidaciones',
        'prestaciones.gestionar' => 'Calcular aguinaldos, otorgar vacaciones y liquidar empleados',
        'exportaciones.generar' => 'Descargar los archivos para ISSS, AFP y Hacienda',
        'usuarios.gestionar' => 'Crear cuentas y asignar roles',
    ];

    public function run(): void
    {
        // Limpia la caché de permisos antes de sembrar (obligatorio en spatie).
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (array_keys(self::PERMISOS) as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }

        // Segundo vaciado de caché: los permisos recién creados deben ser
        // visibles para syncPermissions(). Es imprescindible cuando el seeder
        // corre con los eventos de modelo silenciados (WithoutModelEvents),
        // porque entonces spatie no invalida su caché por su cuenta.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Administrador: acceso total (además, Gate::before lo deja pasar a todo).
        Role::findOrCreate(User::ROL_ADMIN, 'web')
            ->syncPermissions(array_keys(self::PERMISOS));

        // Recursos Humanos: opera el sistema, pero no administra cuentas.
        Role::findOrCreate(User::ROL_RRHH, 'web')->syncPermissions([
            'empleados.ver',
            'empleados.gestionar',
            'departamentos.gestionar',
            'planillas.ver',
            'planillas.generar',
            'asistencias.ver',
            'prestaciones.ver',
            'prestaciones.gestionar',
            'exportaciones.generar',
        ]);

        // Empleado: sin permisos administrativos. Su acceso se limita al
        // autoservicio (/mis-recibos y /mi-asistencia), que solo exige sesión.
        Role::findOrCreate(User::ROL_EMPLEADO, 'web')->syncPermissions([]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
