<?php

namespace Database\Seeders;

use App\Models\Empleado;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Deja el sistema listo para usarse: roles, datos demo y las tres
     * cuentas de acceso que representan cada nivel de permisos.
     */
    public function run(): void
    {
        $this->call([
            RolesYPermisosSeeder::class,
            DemoSeeder::class,
        ]);

        // 1. Administrador del sistema
        $admin = User::firstOrCreate(
            ['email' => 'admin@rrhh.test'],
            ['name' => 'Administrador', 'password' => Hash::make('password'), 'activo' => true]
        );
        $admin->syncRoles([User::ROL_ADMIN]);

        // 2. Analista de Recursos Humanos
        $rrhh = User::firstOrCreate(
            ['email' => 'rrhh@rrhh.test'],
            ['name' => 'Analista de RRHH', 'password' => Hash::make('password'), 'activo' => true]
        );
        $rrhh->syncRoles([User::ROL_RRHH]);

        // 3. Empleado con autoservicio, enlazado a su ficha de planilla
        $empleado = Empleado::where('dui', '04567890-1')->first();

        if ($empleado) {
            $cuenta = User::firstOrCreate(
                ['email' => 'empleado@rrhh.test'],
                [
                    'name' => $empleado->nombre_completo,
                    'password' => Hash::make('password'),
                    'empleado_id' => $empleado->id,
                    'activo' => true,
                ]
            );
            $cuenta->syncRoles([User::ROL_EMPLEADO]);
        }
    }
}
