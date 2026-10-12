<?php

namespace App\Http\Controllers;

use App\Actions\Clientes\DeleteCliente;
use App\Actions\Clientes\SaveCliente;
use App\Actions\Clientes\ToggleClienteActivation;
use App\Http\Requests\Clientes\StoreClienteRequest;
use App\Http\Requests\Clientes\UpdateClienteRequest;
use App\Models\Cliente;
use App\Support\Clientes\ClienteEnUsoException;
use App\Support\Clientes\ClientePayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Clientes: the agency's commercial directory.
 *
 * Filtering, sorting and pagination are resolved on the server, as the database
 * standard requires: the list grows without bound and shipping it to the client
 * would stop working exactly when the agency has enough clients to need search.
 */
#[Authorize('viewAny', Cliente::class, only: ['index'])]
#[Authorize('create', Cliente::class, only: ['create', 'store'])]
#[Authorize('view', 'cliente', only: ['show'])]
#[Authorize('update', 'cliente', only: ['edit', 'update'])]
#[Authorize('archive', 'cliente', only: ['toggle'])]
#[Authorize('delete', 'cliente', only: ['destroy'])]
class ClientesController extends Controller
{
    /**
     * Rows per page the list offers, taken from the prototype.
     *
     * @var list<int>
     */
    private const PER_PAGE = [5, 10, 25];

    public function index(Request $request): Response
    {
        $search = (string) $request->string('buscar');
        $estado = (string) $request->string('estado', 'todos');
        $perPage = (int) $request->integer('filas', 10);

        if (! in_array($perPage, self::PER_PAGE, true)) {
            $perPage = 10;
        }

        $clientes = Cliente::query()
            ->with('contactoPrincipal')
            ->search($search)
            ->when($estado === 'activos', fn ($query) => $query->where('is_active', true))
            ->when($estado === 'inactivos', fn ($query) => $query->where('is_active', false))
            ->orderBy('nombre')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Cliente $cliente): array => ClientePayload::row($cliente));

        return Inertia::render('clientes/index', [
            'clientes' => $clientes,
            'filtros' => ['buscar' => $search, 'estado' => $estado, 'filas' => $perPage],
            'resumen' => $this->summary(),
            'opciones_filas' => self::PER_PAGE,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('clientes/form', [
            'cliente' => ClientePayload::formValues(null),
            'opciones' => ClientePayload::options(),
        ]);
    }

    public function store(StoreClienteRequest $request, SaveCliente $save): RedirectResponse
    {
        $cliente = $save->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cliente creado']);

        return to_route('clientes.show', $cliente);
    }

    public function show(Cliente $cliente): Response
    {
        $cliente->load(['contactos', 'tipoDocumento', 'regimen']);

        return Inertia::render('clientes/show', [
            'cliente' => ClientePayload::detail($cliente),
        ]);
    }

    public function edit(Cliente $cliente): Response
    {
        $cliente->load('contactos');

        return Inertia::render('clientes/form', [
            'cliente' => ClientePayload::formValues($cliente),
            'opciones' => ClientePayload::options(),
        ]);
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente, SaveCliente $save): RedirectResponse
    {
        $save->update($cliente, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cliente actualizado']);

        return to_route('clientes.show', $cliente);
    }

    public function toggle(Cliente $cliente, ToggleClienteActivation $toggle): RedirectResponse
    {
        $wasActive = $cliente->is_active;

        $toggle($cliente);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $wasActive ? 'Cliente archivado como inactivo' : 'Cliente reactivado',
        ]);

        return to_route('clientes.show', $cliente);
    }

    /**
     * The screen only offers this when nothing points at the client, but the
     * refusal is enforced here too: the count that drives the screen is derived
     * from references declared in code, and the foreign key is what actually
     * keeps the history.
     */
    public function destroy(Cliente $cliente, DeleteCliente $delete): RedirectResponse
    {
        $nombre = $cliente->nombre;

        try {
            $delete($cliente);
        } catch (ClienteEnUsoException) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => "No se puede eliminar «{$nombre}»: tiene historial asociado. Archívalo como inactivo.",
            ]);

            return to_route('clientes.show', $cliente);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => "«{$nombre}» eliminado"]);

        return to_route('clientes.index');
    }

    /**
     * @return array<string, int>
     */
    private function summary(): array
    {
        return [
            'total' => Cliente::query()->count(),
            'activos' => Cliente::query()->where('is_active', true)->count(),
        ];
    }
}
