<?php

/*
|--------------------------------------------------------------------------
| Prestaciones laborales — El Salvador
|--------------------------------------------------------------------------
| Reglas del Código de Trabajo parametrizadas. Están aquí y no en el código
| porque son las que más cambian por reforma o por política interna de la
| empresa (una empresa puede dar MÁS que el mínimo legal, nunca menos).
|
| IMPORTANTE: verificar los montos y porcentajes contra la normativa vigente
| antes de usarlo en producción. El sistema respeta lo que diga este archivo.
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Aguinaldo (Código de Trabajo, Arts. 196-202)
    |--------------------------------------------------------------------------
    | Días de salario según la antigüedad al 12 de diciembre. Quien no tenga
    | un año cumplido recibe la parte proporcional al tiempo laborado.
    */
    'aguinaldo' => [
        'tramos' => [
            ['desde_anios' => 1,  'hasta_anios' => 3,    'dias' => 15],
            ['desde_anios' => 3,  'hasta_anios' => 10,   'dias' => 19],
            ['desde_anios' => 10, 'hasta_anios' => null, 'dias' => 21],
        ],

        // Base para el cálculo proporcional de quien tiene menos de un año.
        'dias_proporcional' => 15,

        // Fecha de corte legal para medir la antigüedad (día/mes).
        'dia_corte' => 12,
        'mes_corte' => 12,

        // Porción exenta de renta, expresada en salarios mínimos mensuales.
        'exento_salarios_minimos' => 2,

        // El aguinaldo no es salario para efectos previsionales.
        'cotiza_isss' => false,
        'cotiza_afp' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Vacaciones (Código de Trabajo, Arts. 177-189)
    |--------------------------------------------------------------------------
    | 15 días remunerados tras cada año continuo de trabajo, pagados con un
    | recargo del 30% sobre el salario ordinario de esos días.
    */
    'vacaciones' => [
        'dias_por_anio' => 15,
        'recargo' => 0.30,
        'anios_minimos' => 1,

        // La remuneración de vacaciones sí es salario: cotiza y grava.
        'cotiza_isss' => true,
        'cotiza_afp' => true,
        'grava_renta' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Horas extra y recargos (Código de Trabajo, Arts. 161-170)
    |--------------------------------------------------------------------------
    | La jornada ordinaria diurna es de 8 horas. El tiempo que exceda se paga
    | con un recargo del 100%. El sistema descuenta el tiempo de refrigerio
    | antes de considerar que hubo tiempo extraordinario.
    */
    'horas_extra' => [
        'jornada_ordinaria_minutos' => 480,
        'descanso_minutos' => 60,

        'recargo_diurna' => 1.00,    // se paga el doble
        'recargo_nocturna' => 1.25,  // jornada nocturna: 25% adicional

        'inicio_jornada_nocturna' => '19:00',

        // Tope diario de tiempo extraordinario reconocido, para que un olvido
        // de marcaje de salida no se convierta en una jornada de 14 horas.
        'maximo_minutos_dia' => 240,

        'cotiza_isss' => true,
        'cotiza_afp' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Terminación de la relación laboral
    |--------------------------------------------------------------------------
    */
    'liquidacion' => [
        // Despido sin causa justificada: 30 días de salario por año de servicio,
        // con el salario diario topado a 4 salarios mínimos diarios (Art. 58).
        'despido' => [
            'dias_por_anio' => 30,
            'tope_salarios_minimos_diarios' => 4,
        ],

        // Prestación económica por renuncia voluntaria (D.L. 592):
        // 15 días de salario por año para quien tenga 2 años o más.
        'renuncia' => [
            'dias_por_anio' => 15,
            'anios_minimos' => 2,
            'tope_salarios_minimos' => 2,
        ],

        'motivos' => [
            'despido_injustificado' => 'Despido sin causa justificada',
            'renuncia_voluntaria' => 'Renuncia voluntaria',
            'mutuo_acuerdo' => 'Terminación por mutuo acuerdo',
            'despido_justificado' => 'Despido con causa justificada',
            'fin_de_contrato' => 'Vencimiento del plazo del contrato',
        ],

        // Motivos que generan derecho a indemnización por despido.
        'motivos_con_indemnizacion' => ['despido_injustificado'],

        // Motivos que generan la prestación por renuncia voluntaria.
        'motivos_con_prestacion_renuncia' => ['renuncia_voluntaria'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Incapacidades (Código de Trabajo, Art. 307)
    |--------------------------------------------------------------------------
    | Por enfermedad común el patrono cubre los primeros días al 75% del
    | salario básico; a partir de ahí el subsidio lo paga el ISSS.
    */
    'incapacidad' => [
        'dias_a_cargo_del_patrono' => 3,
        'porcentaje_patrono' => 0.75,
    ],

];
