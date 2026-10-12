<?php

namespace App\Http\Controllers;

use App\Actions\Catalogo\DeleteCatalogValue;
use App\Actions\Catalogo\MoveCatalogValue;
use App\Actions\Catalogo\SaveCatalogValue;
use App\Actions\Catalogo\SetCatalogValueAsDefault;
use App\Actions\Catalogo\ToggleCatalogValueActivation;
use App\Http\Requests\Maestros\MoveCatalogValueRequest;
use App\Http\Requests\Maestros\StoreCatalogValueRequest;
use App\Http\Requests\Maestros\UpdateCatalogValueRequest;
use App\Models\CatalogList;
use App\Models\CatalogValue;
use App\Support\Catalogo\CatalogPayload;
use App\Support\Catalogo\CatalogValueInUseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Maestros y Listas: the enumerations the agency manages without a deploy.
 *
 * Every ability is checked through a policy rather than a role test in the
 * controller, so the protection of a value the code branches on holds for every
 * role including the owner.
 */
#[Authorize('viewAny', CatalogList::class, only: ['index'])]
#[Authorize('create', CatalogValue::class, only: ['store'])]
#[Authorize('update', 'value', only: ['update'])]
#[Authorize('delete', 'value', only: ['destroy'])]
#[Authorize('setDefault', 'value', only: ['setDefault'])]
#[Authorize('deactivate', 'value', only: ['toggle'])]
#[Authorize('move', 'value', only: ['move'])]
class MaestrosController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('maestros/index', CatalogPayload::build());
    }

    public function store(StoreCatalogValueRequest $request, CatalogList $list, SaveCatalogValue $save): RedirectResponse
    {
        $save->create($list, $request->validated());

        return $this->back('success', 'Valor agregado');
    }

    public function update(UpdateCatalogValueRequest $request, CatalogValue $value, SaveCatalogValue $save): RedirectResponse
    {
        $save->update($value, $request->validated());

        return $this->back('success', 'Valor actualizado');
    }

    public function setDefault(CatalogValue $value, SetCatalogValueAsDefault $setDefault): RedirectResponse
    {
        $setDefault($value);

        return $this->back('success', 'Valor marcado como predeterminado');
    }

    public function toggle(CatalogValue $value, ToggleCatalogValueActivation $toggle): RedirectResponse
    {
        $wasActive = $value->is_active;

        $toggle($value);

        return $this->back('success', $wasActive ? 'Valor desactivado' : 'Valor reactivado');
    }

    public function move(MoveCatalogValueRequest $request, CatalogValue $value, MoveCatalogValue $move): RedirectResponse
    {
        $move($value, $request->string('direction')->value() === 'up' ? -1 : 1);

        return to_route('maestros.index');
    }

    /**
     * The screen already offers deactivation instead of deletion for a value in
     * use, but the refusal is enforced here too: the count that drives the
     * screen is derived from the references declared in code, and the foreign
     * key is what actually guarantees history survives.
     */
    public function destroy(CatalogValue $value, DeleteCatalogValue $delete): RedirectResponse
    {
        $name = $value->name;

        try {
            $delete($value);
        } catch (CatalogValueInUseException) {
            return $this->back(
                'error',
                "No se puede eliminar «{$name}»: está en uso. Desactívalo para que no aparezca en registros nuevos.",
            );
        }

        return $this->back('success', 'Valor eliminado');
    }

    private function back(string $type, string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => $type, 'message' => $message]);

        return to_route('maestros.index');
    }
}
