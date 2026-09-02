<?php

namespace App\Policies;

use App\Models\Aguinaldo;
use App\Models\User;

/**
 * RRHH ve el aguinaldo de todos; el empleado, solo el suyo.
 */
class AguinaldoPolicy
{
    public function view(User $user, Aguinaldo $aguinaldo): bool
    {
        if ($user->can('prestaciones.ver')) {
            return true;
        }

        return $user->empleado_id !== null
            && $user->empleado_id === $aguinaldo->empleado_id;
    }
}
