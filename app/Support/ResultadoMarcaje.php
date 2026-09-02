<?php

namespace App\Support;

/**
 * Resultado de un intento de marcaje en el kiosco.
 *
 * 'nivel' se mapea directo a la clave de sesión que pinta la pantalla
 * (success / warning / error), y 'tipo' permite a la vista distinguir
 * entre una entrada y una salida para cambiar el color de la tarjeta.
 */
class ResultadoMarcaje
{
    public const ENTRADA = 'entrada';
    public const SALIDA = 'salida';
    public const BLOQUEADO = 'bloqueado';
    public const DUPLICADO = 'duplicado';
    public const RECHAZADO = 'rechazado';

    public function __construct(
        public readonly string $tipo,
        public readonly string $nivel,
        public readonly string $mensaje,
    ) {
    }

    public static function entrada(string $mensaje): self
    {
        return new self(self::ENTRADA, 'success', $mensaje);
    }

    public static function salida(string $mensaje): self
    {
        return new self(self::SALIDA, 'success', $mensaje);
    }

    public static function bloqueado(string $mensaje): self
    {
        return new self(self::BLOQUEADO, 'warning', $mensaje);
    }

    public static function duplicado(string $mensaje): self
    {
        return new self(self::DUPLICADO, 'warning', $mensaje);
    }

    public static function rechazado(string $mensaje): self
    {
        return new self(self::RECHAZADO, 'error', $mensaje);
    }

    public function fueExitoso(): bool
    {
        return in_array($this->tipo, [self::ENTRADA, self::SALIDA], true);
    }
}
