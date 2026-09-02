<?php

namespace App\Http\Requests;

use App\Models\Empleado;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmpleadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('empleados.gestionar') ?? false;
    }

    /**
     * Deja el DUI siempre en formato 00000000-0 antes de validar,
     * sin importar si se digitó con o sin guion.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('dui')) {
            $this->merge(['dui' => Empleado::normalizarDui($this->input('dui'))]);
        }
    }

    public function rules(): array
    {
        $empleadoId = $this->route('empleado')?->id ?? $this->route('empleado');

        return [
            'nombres' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'dui' => [
                'required',
                'string',
                'regex:/^\d{8}-\d$/',
                Rule::unique('empleados', 'dui')->ignore($empleadoId),
            ],
            // Mayor de 18 años: la fecha debe ser anterior a hace 18 años.
            'fecha_nacimiento' => ['required', 'date', 'before:'.now()->subYears(18)->toDateString()],
            'fecha_contratacion' => ['required', 'date', 'after:fecha_nacimiento', 'before_or_equal:today'],
            'salario_base' => ['required', 'numeric', 'min:365', 'max:99999.99'],
            'departamento_id' => ['required', 'exists:departamentos,id'],
            'activo' => ['sometimes', 'boolean'],

            // Opcionales al dar de alta, obligatorios en la práctica antes de
            // generar los archivos previsionales.
            'nit' => ['nullable', 'string', 'max:20'],
            'numero_isss' => ['nullable', 'string', 'max:20'],
            'numero_afp' => ['nullable', 'string', 'max:20'],
            'afp_administradora' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'dui.regex' => 'El DUI debe tener el formato 00000000-0.',
            'dui.unique' => 'Ya existe un empleado registrado con ese DUI.',
            'fecha_nacimiento.before' => 'El empleado debe ser mayor de 18 años.',
            'fecha_contratacion.before_or_equal' => 'La fecha de contratación no puede estar en el futuro.',
            'salario_base.min' => 'El salario no puede ser menor al salario mínimo vigente ($365.00).',
        ];
    }

    public function attributes(): array
    {
        return [
            'departamento_id' => 'departamento',
            'salario_base' => 'salario base',
            'fecha_nacimiento' => 'fecha de nacimiento',
            'fecha_contratacion' => 'fecha de contratación',
        ];
    }
}
