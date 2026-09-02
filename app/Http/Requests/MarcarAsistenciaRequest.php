<?php

namespace App\Http\Requests;

use App\Models\Empleado;
use Illuminate\Foundation\Http\FormRequest;

class MarcarAsistenciaRequest extends FormRequest
{
    /**
     * El kiosco es público (una tablet en recepción, sin sesión iniciada);
     * la protección real es el rate limiting + el cooldown por empleado.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Los lectores de códigos QR suelen enviar el DUI sin guion o con espacios
     * o saltos de línea al final. Aquí se normaliza antes de validar.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'dui' => Empleado::normalizarDui((string) $this->input('dui')),
        ]);
    }

    public function rules(): array
    {
        return [
            'dui' => ['required', 'string', 'regex:/^\d{8}-\d$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'dui.required' => 'No se leyó ningún gafete.',
            'dui.regex' => 'El código leído no corresponde a un gafete válido.',
        ];
    }
}
