<?php

namespace App\Actions\Catalogo;

use App\Models\CatalogValue;
use Illuminate\Support\Facades\DB;

/**
 * Moves a value one place up or down its list.
 *
 * Positions are normalised first. The seeder writes them contiguously, but a
 * value added later sits at max+1 and a sequence with gaps makes a swap look
 * like nothing happened.
 *
 * The swap is against the neighbour in the list's canonical order, never
 * against whatever the screen happens to be showing. The prototype swapped
 * within the full array while the user looked at a filtered view, so with a
 * filter active the row traded places with something invisible and appeared
 * frozen. The screen therefore only offers the arrows on an unfiltered list.
 */
class MoveCatalogValue
{
    public function __invoke(CatalogValue $value, int $direction): void
    {
        DB::transaction(function () use ($value, $direction): void {
            $values = $value->list->values()->orderBy('position')->orderBy('id')->get();

            $values->values()->each(function (CatalogValue $ordered, int $index): void {
                if ($ordered->position !== $index) {
                    $ordered->update(['position' => $index]);
                }
            });

            $current = $values->search(fn (CatalogValue $candidate): bool => $candidate->is($value));

            if ($current === false) {
                return;
            }

            $target = $current + ($direction < 0 ? -1 : 1);

            $moving = $values->get($current);
            $neighbour = $values->get($target);

            if ($moving === null || $neighbour === null) {
                return;
            }

            $neighbour->update(['position' => $current]);
            $moving->update(['position' => $target]);
        });
    }
}
