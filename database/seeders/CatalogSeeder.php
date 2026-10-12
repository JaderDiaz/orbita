<?php

namespace Database\Seeders;

use App\Enums\CatalogColor as C;
use App\Enums\CatalogIcon as I;
use App\Models\CatalogList;
use App\Models\CatalogValue;
use App\Support\Catalogo\Catalogo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The catalog the agency starts with: 16 lists across 7 groups, 73 values.
 *
 * Taken literally from the prototype's `msSeed()`. Names, codes, colours,
 * icons, descriptions and defaults are the business's own vocabulary and are
 * reproduced verbatim rather than translated.
 *
 * Idempotency: every seeded value carries a `system_key` slug so re-seeding
 * finds it again even after the agency renames it. Only the six values the code
 * branches on are marked `is_system`; the rest are ordinary user data that
 * happens to ship with the install.
 *
 * Re-seeding deliberately does NOT overwrite a value's name, code, colour,
 * icon, description, default, position or flags. Those belong to the user from
 * the moment the install exists, and a seeder that reverted their edits on
 * every deploy would be worse than no seeder.
 *
 * @phpstan-type CatalogListSeed array{key: string, group: string, title: string, description: string, fields: list<string>, used_in: list<string>, flags?: array{key: string, label: string}, values: list<array<string, mixed>>}
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->catalog() as $position => $list) {
            $this->seedList($list, $position);
        }

        // Flushed explicitly because DatabaseSeeder runs WithoutModelEvents, so
        // the models' saved hooks never fire during a seed.
        Catalogo::flush();
    }

    /**
     * @param  CatalogListSeed  $list
     */
    private function seedList(array $list, int $position): void
    {
        $flags = isset($list['flags']) ? [$list['flags']] : [];

        /** @var CatalogList $model */
        $model = CatalogList::query()->updateOrCreate(
            ['key' => $list['key']],
            [
                'group' => $list['group'],
                'title' => $list['title'],
                'description' => $list['description'],
                'flags' => $flags,
                'fields' => $list['fields'],
                'used_in' => $list['used_in'],
                'allows_user_values' => true,
                'position' => $position,
            ],
        );

        $flagKey = $list['flags']['key'] ?? null;

        foreach ($list['values'] as $index => $value) {
            $this->seedValue($model, $value, $index, $flagKey);
        }
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function seedValue(CatalogList $list, array $value, int $position, ?string $flagKey): void
    {
        /** @var string $name */
        $name = $value['name'];
        $systemKey = Str::slug($name);

        $existing = CatalogValue::query()
            ->where('list_id', $list->id)
            ->where('system_key', $systemKey)
            ->first();

        if ($existing !== null) {
            // Only the protection flag is re-asserted. Everything else is the
            // user's to change.
            $existing->update(['is_system' => (bool) ($value['system'] ?? false)]);

            return;
        }

        $flags = $flagKey !== null && ($value['flag'] ?? false)
            ? [$flagKey => true]
            : [];

        CatalogValue::query()->create([
            'list_id' => $list->id,
            'system_key' => $systemKey,
            'name' => $name,
            'code' => $list->usesField('code') ? ($value['code'] ?? null) : null,
            'color' => $list->usesField('color') ? ($value['color'] ?? null) : null,
            'icon' => $list->usesField('icon') ? ($value['icon'] ?? null) : null,
            'description' => $list->usesField('desc') ? ($value['description'] ?? null) : null,
            'flags' => $flags,
            'is_default' => (bool) ($value['default'] ?? false),
            'is_active' => true,
            'is_system' => (bool) ($value['system'] ?? false),
            'position' => $position,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function value(
        string $name,
        ?string $code = null,
        ?C $color = null,
        ?I $icon = null,
        ?string $description = null,
        bool $flag = false,
        bool $default = false,
        bool $system = false,
    ): array {
        return [
            'name' => $name,
            'code' => $code,
            'color' => $color,
            'icon' => $icon,
            'description' => $description,
            'flag' => $flag,
            'default' => $default,
            'system' => $system,
        ];
    }

    /**
     * @return list<CatalogListSeed>
     */
    private function catalog(): array
    {
        return [
            [
                'key' => 'tipos-documento',
                'group' => 'Clientes y fiscal',
                'title' => 'Tipos de documento',
                'description' => 'Identificación de clientes y contactos. El código se usa en vistas compactas.',
                'fields' => ['code', 'desc'],
                'used_in' => ['Clientes', 'Facturas'],
                'values' => [
                    $this->value('NIT', code: 'NIT', description: 'Personas jurídicas', default: true),
                    $this->value('Cédula de ciudadanía', code: 'CC'),
                    $this->value('Cédula de extranjería', code: 'CE'),
                    $this->value('Pasaporte', code: 'PA'),
                    $this->value('PPT', code: 'PPT', description: 'Permiso por protección temporal'),
                ],
            ],
            [
                'key' => 'regimenes',
                'group' => 'Clientes y fiscal',
                'title' => 'Regímenes tributarios',
                'description' => 'Define el tratamiento de IVA y retenciones del cliente.',
                'fields' => ['code'],
                'used_in' => ['Clientes', 'Facturas', 'Parámetros'],
                'flags' => ['key' => 'cobra_iva', 'label' => 'Cobra IVA'],
                'values' => [
                    $this->value('Responsable de IVA', code: 'R-48', flag: true, default: true, system: true),
                    $this->value('No responsable de IVA', code: 'R-49'),
                    $this->value('Régimen simple de tributación', code: 'RST', flag: true),
                    $this->value('Gran contribuyente', code: 'GC', flag: true),
                ],
            ],
            [
                'key' => 'estados-cotizacion',
                'group' => 'Comercial',
                'title' => 'Estados de cotización',
                'description' => 'Ciclo de una cotización desde el borrador hasta la decisión del cliente.',
                'fields' => ['color'],
                'used_in' => ['Cotizaciones', 'Clientes'],
                'flags' => ['key' => 'estado_final', 'label' => 'Estado final'],
                'values' => [
                    $this->value('Borrador', color: C::Gris, default: true),
                    $this->value('Enviada', color: C::Azul),
                    $this->value('Aprobada', color: C::Verde, flag: true, system: true),
                    $this->value('Rechazada', color: C::Rojo, flag: true),
                    $this->value('Vencida', color: C::Naranja, flag: true),
                ],
            ],
            [
                'key' => 'modalidades',
                'group' => 'Proyectos',
                'title' => 'Modalidades de contrato',
                'description' => 'Cómo se cobra el proyecto. Determina el cálculo de presupuesto y margen.',
                'fields' => ['code', 'desc'],
                'used_in' => ['Proyectos', 'Cotizaciones', 'Parámetros'],
                'values' => [
                    $this->value('Precio cerrado', code: 'PC', description: 'Valor fijo por alcance', default: true, system: true),
                    $this->value('Por hora', code: 'PH', description: 'Se factura por horas registradas'),
                    $this->value('Retainer mensual', code: 'RM', description: 'Cuota fija mensual'),
                ],
            ],
            [
                'key' => 'estados-proyecto',
                'group' => 'Proyectos',
                'title' => 'Estados de proyecto',
                'description' => 'Etapas generales del proyecto en la lista y el panel.',
                'fields' => ['color'],
                'used_in' => ['Proyectos', 'Panel'],
                'flags' => ['key' => 'cuenta_como_activo', 'label' => 'Cuenta como activo'],
                'values' => [
                    $this->value('En negociación', color: C::Indigo),
                    $this->value('En desarrollo', color: C::Azul, flag: true, default: true),
                    $this->value('En pruebas', color: C::Morado, flag: true),
                    $this->value('Pausado', color: C::Naranja),
                    $this->value('Entregado', color: C::Verde),
                ],
            ],
            [
                'key' => 'estados-fase',
                'group' => 'Proyectos',
                'title' => 'Estados de fase',
                'description' => 'Avance de cada fase y su aceptación por el cliente.',
                'fields' => ['color'],
                'used_in' => ['Proyectos', 'Facturas'],
                'flags' => ['key' => 'estado_final', 'label' => 'Estado final'],
                'values' => [
                    $this->value('Planeada', color: C::Gris, default: true),
                    $this->value('En curso', color: C::Azul),
                    $this->value('En aceptación', color: C::Morado),
                    $this->value('Aceptada', color: C::Verde, flag: true, system: true),
                ],
            ],
            [
                'key' => 'disciplinas',
                'group' => 'Proyectos',
                'title' => 'Disciplinas',
                'description' => 'Áreas de trabajo dentro de cada fase.',
                'fields' => ['code', 'color'],
                'used_in' => ['Proyectos', 'Tareas'],
                'values' => [
                    // No default on purpose: no discipline is the obvious
                    // preselection, which is why the schema allows at most one
                    // default rather than requiring exactly one.
                    $this->value('Descubrimiento', code: 'DSC', color: C::Indigo),
                    $this->value('UI/UX', code: 'UX', color: C::Morado),
                    $this->value('Backend', code: 'BE', color: C::Azul),
                    $this->value('Frontend', code: 'FE', color: C::Turquesa),
                    $this->value('QA y entrega', code: 'QA', color: C::Verde),
                    $this->value('Soporte', code: 'SOP', color: C::Gris),
                ],
            ],
            [
                'key' => 'tipos-tarea',
                'group' => 'Tareas',
                'title' => 'Tipos de tarea',
                'description' => 'Clasificación de las tarjetas del tablero.',
                'fields' => ['code', 'color', 'icon'],
                'used_in' => ['Tareas', 'Mi tiempo'],
                'values' => [
                    $this->value('Feature', code: 'FEAT', color: C::Azul, icon: I::Sparkle, default: true),
                    $this->value('Bug', code: 'BUG', color: C::Rojo, icon: I::Bug),
                    $this->value('Mejora', code: 'MEJ', color: C::Morado, icon: I::Up),
                ],
            ],
            [
                'key' => 'prioridades',
                'group' => 'Tareas',
                'title' => 'Prioridades',
                'description' => 'Urgencia de las tareas.',
                'fields' => ['color', 'icon'],
                'used_in' => ['Tareas'],
                'values' => [
                    $this->value('Alta', color: C::Rojo, icon: I::Up),
                    $this->value('Media', color: C::Naranja, icon: I::Minus, default: true),
                    $this->value('Baja', color: C::Gris, icon: I::Down),
                ],
            ],
            [
                'key' => 'columnas-tablero',
                'group' => 'Tareas',
                'title' => 'Columnas del tablero',
                'description' => 'Estados de una tarea en el tablero kanban.',
                'fields' => ['color'],
                'used_in' => ['Tareas'],
                'flags' => ['key' => 'cierra_la_tarea', 'label' => 'Cierra la tarea'],
                'values' => [
                    $this->value('Por hacer', color: C::Gris, default: true),
                    $this->value('En progreso', color: C::Azul),
                    $this->value('Revisión', color: C::Morado),
                    $this->value('Hecho', color: C::Verde, flag: true, system: true),
                ],
            ],
            [
                'key' => 'estados-factura',
                'group' => 'Finanzas',
                'title' => 'Estados de factura',
                'description' => 'Situación de cobro de cada factura.',
                'fields' => ['color'],
                'used_in' => ['Facturas', 'Clientes', 'Panel'],
                'flags' => ['key' => 'saldo_en_cero', 'label' => 'Saldo en cero'],
                'values' => [
                    $this->value('Pendiente', color: C::Azul, default: true),
                    $this->value('Parcial', color: C::Naranja),
                    $this->value('Pagada', color: C::Verde, flag: true, system: true),
                    // Kept because the prototype ships it, but note that
                    // `Vencida` is derived from `vence < today AND saldo > 0`
                    // and must never be assigned as a stored lifecycle state.
                    $this->value('Vencida', color: C::Rojo),
                ],
            ],
            [
                'key' => 'metodos-pago',
                'group' => 'Finanzas',
                'title' => 'Métodos de pago',
                'description' => 'Cómo se recibe un pago dentro de una factura.',
                'fields' => ['code', 'icon'],
                'used_in' => ['Facturas'],
                'values' => [
                    $this->value('Transferencia', code: 'TRF', icon: I::Bank, default: true),
                    $this->value('Consignación', code: 'CON', icon: I::Cash),
                    $this->value('Efectivo', code: 'EFE', icon: I::Cash),
                    $this->value('PSE', code: 'PSE', icon: I::Card),
                ],
            ],
            [
                'key' => 'categorias-egreso',
                'group' => 'Finanzas',
                'title' => 'Categorías de egreso',
                'description' => 'Agrupan los gastos en el resumen de egresos.',
                'fields' => ['code', 'color', 'desc'],
                'used_in' => ['Egresos', 'Parámetros'],
                'values' => [
                    $this->value('Infraestructura', code: 'INF', color: C::Azul, description: 'Servidores, dominios y nube', default: true),
                    $this->value('Licencias', code: 'LIC', color: C::Morado, description: 'Software y suscripciones'),
                    $this->value('Contratistas', code: 'CTR', color: C::Naranja, description: 'Servicios profesionales externos'),
                    $this->value('Operación', code: 'OPE', color: C::Gris, description: 'Oficina y gastos generales'),
                ],
            ],
            [
                'key' => 'categorias-documento',
                'group' => 'Documentos',
                'title' => 'Categorías de documento',
                'description' => 'Organizan los archivos de clientes y proyectos.',
                'fields' => ['code', 'icon'],
                'used_in' => ['Documentos', 'Proyectos', 'Clientes'],
                'values' => [
                    $this->value('Contrato', code: 'CTO', icon: I::Pen, default: true),
                    $this->value('Requerimientos', code: 'REQ', icon: I::File),
                    $this->value('Acta', code: 'ACT', icon: I::Check),
                    $this->value('Manual', code: 'MAN', icon: I::File),
                    $this->value('Técnico', code: 'TEC', icon: I::Box),
                    $this->value('Soporte', code: 'SOP', icon: I::Card),
                ],
            ],
            [
                'key' => 'etiquetas-documento',
                'group' => 'Documentos',
                'title' => 'Etiquetas de documento',
                'description' => 'Marcas libres para filtrar documentos.',
                'fields' => ['color'],
                'used_in' => ['Documentos'],
                'values' => [
                    $this->value('Firmado', color: C::Verde),
                    $this->value('Vigente', color: C::Azul),
                    $this->value('Confidencial', color: C::Rojo),
                    $this->value('Cliente', color: C::Turquesa),
                    $this->value('Interno', color: C::Gris, default: true),
                    $this->value('Borrador', color: C::Naranja),
                ],
            ],
            [
                'key' => 'roles-equipo',
                'group' => 'Equipo',
                'title' => 'Roles del equipo',
                'description' => 'Cargo de cada miembro en Equipo y costos.',
                'fields' => ['code', 'desc'],
                'used_in' => ['Equipo y costos', 'Usuarios y roles'],
                'values' => [
                    // Job titles with ZERO authorization meaning. The system
                    // roles are the SystemRole PHP enum; conflating the two is
                    // how a fourth role ends up bypassing the money boundary.
                    $this->value('Frontend', code: 'FE', default: true),
                    $this->value('Backend', code: 'BE'),
                    $this->value('Full stack', code: 'FS'),
                    $this->value('UI/UX', code: 'UX'),
                    $this->value('QA', code: 'QA'),
                    $this->value('Project manager', code: 'PM'),
                    $this->value('Administrador', code: 'ADM', description: 'Dirección y operación'),
                ],
            ],
        ];
    }
}
