<?php

namespace App\Enums\Catalogo;

use App\Support\Catalogo\SystemKey;

/**
 * Columnas del tablero the code branches on. Reaching this one closes a tarea.
 */
enum ColumnaTablero: string implements SystemKey
{
    case Hecho = 'hecho';

    public function listKey(): string
    {
        return 'columnas-tablero';
    }

    public function systemKey(): string
    {
        return $this->value;
    }
}
