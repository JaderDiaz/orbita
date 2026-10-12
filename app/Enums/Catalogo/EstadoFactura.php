<?php

namespace App\Enums\Catalogo;

use App\Support\Catalogo\SystemKey;

/**
 * Estados de factura the code branches on.
 *
 * Note that `Vencida` is deliberately absent: it is derived from
 * `vence < today AND saldo > 0`, never stored. Only lifecycle states live here.
 */
enum EstadoFactura: string implements SystemKey
{
    case Pagada = 'pagada';

    public function listKey(): string
    {
        return 'estados-factura';
    }

    public function systemKey(): string
    {
        return $this->value;
    }
}
