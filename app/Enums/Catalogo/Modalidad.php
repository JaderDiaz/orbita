<?php

namespace App\Enums\Catalogo;

use App\Support\Catalogo\SystemKey;

/**
 * Modalidades de contrato the code branches on. The modalidad decides how a
 * proyecto's budget and margin are computed.
 */
enum Modalidad: string implements SystemKey
{
    case PrecioCerrado = 'precio-cerrado';

    public function listKey(): string
    {
        return 'modalidades';
    }

    public function systemKey(): string
    {
        return $this->value;
    }
}
