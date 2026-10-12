<?php

namespace App\Support\Catalogo;

/**
 * A catalog value the code addresses by name.
 *
 * Most catalog values are purely user data: the agency renames them freely and
 * nothing in the application cares. A few carry behaviour — a cotización being
 * `Aprobada` unlocks a proyecto, a factura being `Pagada` leaves the receivable
 * — and those must be addressable without comparing against a display name the
 * user can edit. Each enum implementing this interface declares that contract.
 */
interface SystemKey
{
    /**
     * The `catalog_lists.key` this value belongs to.
     */
    public function listKey(): string;

    /**
     * The immutable `catalog_values.system_key`.
     */
    public function systemKey(): string;
}
