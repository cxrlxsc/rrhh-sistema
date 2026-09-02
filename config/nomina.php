<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Datos de la empresa (aparecen en recibos y reportes)
    |--------------------------------------------------------------------------
    */
    'empresa' => [
        'nombre' => env('EMPRESA_NOMBRE', 'Empresa Demo S.A. de C.V.'),
        'nit' => env('EMPRESA_NIT', '0614-000000-000-0'),
        'nrc' => env('EMPRESA_NRC', '000000-0'),
        'direccion' => env('EMPRESA_DIRECCION', 'San Salvador, El Salvador'),
    ],

    /*
    |--------------------------------------------------------------------------
    | ISSS - Régimen de Salud
    |--------------------------------------------------------------------------
    | El trabajador cotiza el 3% y el patrono el 7.5%, ambos sobre un salario
    | máximo cotizable de $1,000.00. Por eso el descuento del empleado nunca
    | supera los $30.00 mensuales.
    */
    'isss' => [
        'tasa_empleado' => 0.03,
        'tasa_patronal' => 0.075,
        'techo_cotizable' => 1000.00,
    ],

    /*
    |--------------------------------------------------------------------------
    | AFP - Sistema de Ahorro para Pensiones
    |--------------------------------------------------------------------------
    | Tras la reforma de la Ley Integral del Sistema de Pensiones (2022) el
    | trabajador aporta 7.25% y el empleador 8.75%. El techo cotizable de AFP
    | es mucho más alto que el del ISSS.
    */
    'afp' => [
        'tasa_empleado' => 0.0725,
        'tasa_patronal' => 0.0875,
        'techo_cotizable' => 6377.14,
    ],

    /*
    |--------------------------------------------------------------------------
    | Impuesto Sobre la Renta (ISR) - Retención mensual
    |--------------------------------------------------------------------------
    | Tablas de retención del Ministerio de Hacienda (Anexo 1 del Reglamento
    | de la Ley de ISR, D.E. 95). La renta se calcula sobre la base imponible:
    |
    |     base = salario devengado - ISSS - AFP
    |
    | y cada tramo aplica:  cuota_fija + (base - excedente_sobre) * porcentaje
    |
    | 'hasta' => null significa "en adelante" (último tramo).
    */
    'renta' => [
        'periodicidad_por_defecto' => 'mensual',

        'tablas' => [

            'mensual' => [
                ['tramo' => 'I',   'desde' => 0.01,    'hasta' => 472.00, 'porcentaje' => 0.00, 'sobre_exceso_de' => 0.00,    'cuota_fija' => 0.00],
                ['tramo' => 'II',  'desde' => 472.01,  'hasta' => 895.24, 'porcentaje' => 0.10, 'sobre_exceso_de' => 472.00,  'cuota_fija' => 17.67],
                ['tramo' => 'III', 'desde' => 895.25,  'hasta' => 2038.10, 'porcentaje' => 0.20, 'sobre_exceso_de' => 895.24,  'cuota_fija' => 60.00],
                ['tramo' => 'IV',  'desde' => 2038.11, 'hasta' => null,   'porcentaje' => 0.30, 'sobre_exceso_de' => 2038.10, 'cuota_fija' => 288.57],
            ],

            'quincenal' => [
                ['tramo' => 'I',   'desde' => 0.01,    'hasta' => 236.00,  'porcentaje' => 0.00, 'sobre_exceso_de' => 0.00,    'cuota_fija' => 0.00],
                ['tramo' => 'II',  'desde' => 236.01,  'hasta' => 447.62,  'porcentaje' => 0.10, 'sobre_exceso_de' => 236.00,  'cuota_fija' => 8.83],
                ['tramo' => 'III', 'desde' => 447.63,  'hasta' => 1019.05, 'porcentaje' => 0.20, 'sobre_exceso_de' => 447.62,  'cuota_fija' => 30.00],
                ['tramo' => 'IV',  'desde' => 1019.06, 'hasta' => null,    'porcentaje' => 0.30, 'sobre_exceso_de' => 1019.05, 'cuota_fija' => 144.28],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reglas generales de la planilla
    |--------------------------------------------------------------------------
    */
    'dias_mes_comercial' => 30,

];
