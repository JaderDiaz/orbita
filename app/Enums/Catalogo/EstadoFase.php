<?php

namespace App\Enums\Catalogo;

use App\Support\Catalogo\SystemKey;

/**
 * Estados de fase the code branches on. Accepting a fase gates invoicing it.
 */
enum EstadoFase: string implements SystemKey
{
    case Aceptada = 'aceptada';

    public function listKey(): string
    {
        return 'estados-fase';
    }

    public function systemKey(): string
    {
        return $this->value;
    }
}
