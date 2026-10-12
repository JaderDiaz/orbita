<?php

namespace App\Enums\Catalogo;

use App\Support\Catalogo\SystemKey;

/**
 * Estados de cotización the code branches on.
 *
 * The list holds five values and the agency may add more; only this one carries
 * behaviour, so only this one is addressable from code.
 */
enum EstadoCotizacion: string implements SystemKey
{
    case Aprobada = 'aprobada';

    public function listKey(): string
    {
        return 'estados-cotizacion';
    }

    public function systemKey(): string
    {
        return $this->value;
    }
}
