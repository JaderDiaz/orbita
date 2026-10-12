<?php

namespace App\Enums\Catalogo;

use App\Support\Catalogo\SystemKey;

/**
 * Regímenes tributarios the code branches on.
 *
 * The `cobra_iva` flag is what invoicing will actually read, so a régimen the
 * agency adds later behaves correctly with no deploy. This key exists for the
 * one case the code needs to name directly.
 */
enum Regimen: string implements SystemKey
{
    case ResponsableIva = 'responsable-iva';

    public function listKey(): string
    {
        return 'regimenes';
    }

    public function systemKey(): string
    {
        return $this->value;
    }
}
