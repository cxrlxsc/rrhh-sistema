<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerarPlanillaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('planillas.generar') ?? false;
    }

    protected function prepareForValidation(): void
    {
        // Si no se elige período, se procesa el mes en curso.
        $this->mergeIfMissing([
            'mes' => now()->month,
            'anio' => now()->year,
        ]);
    }

    public function rules(): array
    {
        return [
            'mes' => ['required', 'integer', 'between:1,12'],
            'anio' => ['required', 'integer', 'between:2020,'.(now()->year + 1)],
        ];
    }
}
