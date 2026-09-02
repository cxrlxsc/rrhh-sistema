<?php

namespace App\Policies;

use App\Models\Liquidacion;
use App\Models\User;

/**
 * El finiquito es un documento personal: su titular siempre puede descargarlo.
 */
class LiquidacionPolicy
{
    public function view(User $user, Liquidacion $liquidacion): bool
    {
        if ($user->can('prestaciones.ver')) {
            return true;
        }

        return $user->empleado_id !== null
            && $user->empleado_id === $liquidacion->empleado_id;
    }
}
