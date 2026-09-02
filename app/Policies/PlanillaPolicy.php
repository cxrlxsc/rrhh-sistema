<?php

namespace App\Policies;

use App\Models\Planilla;
use App\Models\User;

/**
 * Regla de oro del módulo de nómina:
 * RRHH ve la planilla de todos; el empleado, únicamente la suya.
 */
class PlanillaPolicy
{
    public function verTodas(User $user): bool
    {
        return $user->can('planillas.ver');
    }

    public function view(User $user, Planilla $planilla): bool
    {
        if ($user->can('planillas.ver')) {
            return true;
        }

        return $user->empleado_id !== null
            && $user->empleado_id === $planilla->empleado_id;
    }

    public function create(User $user): bool
    {
        return $user->can('planillas.generar');
    }
}
