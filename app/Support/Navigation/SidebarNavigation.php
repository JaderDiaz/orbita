<?php

namespace App\Support\Navigation;

use App\Enums\SystemRole;
use App\Models\User;

/**
 * Source of truth for what each role sees in the sidebar.
 *
 * This lives in PHP, not TypeScript, on purpose: the navigation is resolved on
 * the server and shared already filtered, so the client never decides
 * permissions. The frontend only maps an item's key to an icon and a hue.
 *
 * Sections whose module is not built yet are returned as `enabled: false` with
 * a null href. They stay visible because the navigation is the map of the
 * product — hiding them would make it lie — and a disabled item does not
 * navigate.
 */
class SidebarNavigation
{
    /**
     * @return list<array{label: string|null, items: list<array<string, mixed>>}>
     */
    public function for(User $user): array
    {
        return $user->system_role->isAdministrative()
            ? $this->administrative()
            : $this->operational();
    }

    /**
     * Administrador and Propietario: the four groups of the prototype.
     *
     * @return list<array{label: string|null, items: list<array<string, mixed>>}>
     */
    private function administrative(): array
    {
        return [
            [
                'label' => null,
                'items' => [
                    $this->item('panel', 'Panel', route('dashboard')),
                ],
            ],
            [
                'label' => 'Comercial',
                'items' => [
                    $this->item('clientes', 'Clientes'),
                    $this->item('cotizaciones', 'Cotizaciones'),
                ],
            ],
            [
                'label' => 'Operación',
                'items' => [
                    $this->item('proyectos', 'Proyectos'),
                    $this->item('documentos', 'Documentos'),
                ],
            ],
            [
                'label' => 'Finanzas',
                'items' => [
                    $this->item('facturas', 'Facturas y pagos'),
                    $this->item('egresos', 'Egresos'),
                ],
            ],
            [
                'label' => 'Administración',
                'items' => [
                    $this->item('equipo', 'Equipo y costos'),
                    [
                        ...$this->item('configuracion', 'Configuración'),
                        'children' => [
                            $this->item('usuarios', 'Usuarios y roles'),
                            $this->item('parametros', 'Parámetros'),
                            $this->item('maestros', 'Maestros y listas'),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Miembro del equipo: execution only, no finanzas.
     *
     * @return list<array{label: string|null, items: list<array<string, mixed>>}>
     */
    private function operational(): array
    {
        return [
            [
                'label' => null,
                'items' => [
                    $this->item('mi-panel', 'Mi panel', route('dashboard')),
                ],
            ],
            [
                'label' => 'Operación',
                'items' => [
                    $this->item('proyectos', 'Proyectos'),
                    $this->item('mi-tiempo', 'Mi tiempo'),
                    $this->item('documentos', 'Documentos'),
                ],
            ],
        ];
    }

    /**
     * A null href marks a section whose module is not built yet.
     *
     * @return array{key: string, title: string, href: string|null, enabled: bool}
     */
    private function item(string $key, string $title, ?string $href = null): array
    {
        return [
            'key' => $key,
            'title' => $title,
            'href' => $href,
            'enabled' => $href !== null,
        ];
    }

    /**
     * Roles that get no sidebar at all. The client portal is phase 2.
     */
    public function isSupported(SystemRole $role): bool
    {
        return $role->isStaff();
    }
}
