<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Jornada laboral
    |--------------------------------------------------------------------------
    | 'hora_limite_entrada' define a partir de qué hora se considera tardanza.
    | 'tolerancia_minutos' es el margen de gracia antes de marcar tardanza.
    */
    'hora_limite_entrada' => env('ASISTENCIA_HORA_LIMITE', '08:00'),
    'tolerancia_minutos' => (int) env('ASISTENCIA_TOLERANCIA', 5),
    'hora_salida_esperada' => env('ASISTENCIA_HORA_SALIDA', '17:00'),

    /*
    |--------------------------------------------------------------------------
    | Anti-spam del kiosco
    |--------------------------------------------------------------------------
    | 'cooldown_segundos'  : tiempo mínimo entre dos marcajes del MISMO empleado.
    |                        Evita que un doble escaneo del gafete cierre la
    |                        jornada por accidente.
    | 'jornada_minima_min' : minutos mínimos que deben transcurrir entre la
    |                        entrada y la salida para aceptar el cierre del día.
    | 'rate_limit_*'       : límite por dispositivo/IP para frenar ráfagas de
    |                        peticiones contra el endpoint público.
    */
    'cooldown_segundos' => (int) env('KIOSCO_COOLDOWN', 60),
    'jornada_minima_min' => (int) env('KIOSCO_JORNADA_MINIMA', 60),
    'rate_limit_intentos' => (int) env('KIOSCO_RATE_LIMIT', 20),
    'rate_limit_ventana_min' => (int) env('KIOSCO_RATE_VENTANA', 1),

    /*
    |--------------------------------------------------------------------------
    | Seguridad del kiosco
    |--------------------------------------------------------------------------
    | Si se define KIOSCO_TOKEN, la pantalla del kiosco solo abre con
    | /kiosco?token=xxxx. Útil porque la ruta es pública (sin login).
    */
    'token' => env('KIOSCO_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Segundos que la pantalla del kiosco muestra el mensaje antes de limpiarse
    |--------------------------------------------------------------------------
    */
    'segundos_mensaje' => 4,

];
