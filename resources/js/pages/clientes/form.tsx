import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import { ArrowLeft, Check } from 'lucide-react';
import { useState } from 'react';
import ClientesController from '@/actions/App/Http/Controllers/ClientesController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { ClienteAvatar } from '@/features/clientes/cliente-avatar';
import { ContactosField } from '@/features/clientes/contactos-field';
import type {
    ClienteFormValues,
    ClienteOptions,
} from '@/features/clientes/types';
import { cn } from '@/lib/utils';
import clientes from '@/routes/clientes';
import { dashboard } from '@/routes';

type Props = {
    cliente: ClienteFormValues;
    opciones: ClienteOptions;
};

const PLACEHOLDERS: Record<string, string> = {
    juridica: '900.000.000-0',
    natural: '1.000.000.000',
};

function Segmented({
    name,
    value,
    options,
    onChange,
}: {
    name: string;
    value: string;
    options: { value: string; label: string }[];
    onChange: (value: string) => void;
}) {
    return (
        <div className="bg-surface-track inline-flex rounded-full p-0.5">
            <input type="hidden" name={name} value={value} />
            {options.map((option) => (
                <button
                    key={option.value}
                    type="button"
                    onClick={() => onChange(option.value)}
                    aria-pressed={value === option.value}
                    className={cn(
                        'rounded-full px-3 py-1 text-[12.5px] transition-colors',
                        value === option.value
                            ? 'bg-card text-foreground shadow-control font-medium'
                            : 'text-text-secondary',
                    )}
                >
                    {option.label}
                </button>
            ))}
        </div>
    );
}

/**
 * Alta y edición de un cliente.
 *
 * The tag is left optional: when it is empty the server derives it from the
 * name and resolves collisions, which is the one place that can know whether a
 * tag is free.
 */
export default function ClienteForm({ cliente, opciones }: Props) {
    const editing = cliente.id !== null;

    const [values, setValues] = useState(cliente);

    setLayoutProps({
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Clientes', href: clientes.index() },
            {
                title: editing ? 'Editar cliente' : 'Nuevo cliente',
                href: editing
                    ? clientes.edit(cliente.id as number)
                    : clientes.create(),
            },
        ],
    });

    const set = (cambios: Partial<ClienteFormValues>): void =>
        setValues((actual) => ({ ...actual, ...cambios }));

    const esJuridica = values.tipo_persona === 'juridica';
    const nombreLabel = esJuridica ? 'Razón social' : 'Nombre completo';

    const action = editing
        ? ClientesController.update.form({ cliente: cliente.id as number })
        : ClientesController.store.form();

    const checklist = [
        { label: nombreLabel, done: values.nombre.trim() !== '' },
        { label: 'Número de documento', done: values.documento.trim() !== '' },
        { label: 'Ciudad y departamento', done: values.ciudad.trim() !== '' && values.departamento.trim() !== '' },
        {
            label: 'Contacto principal con email',
            done: values.contactos.some(
                (contacto) =>
                    contacto.is_principal && contacto.email.trim() !== '',
            ),
        },
    ];

    const listos = checklist.filter((item) => item.done).length;

    return (
        <>
            <Head title={editing ? 'Editar cliente' : 'Nuevo cliente'} />

            <Form {...action} options={{ preserveScroll: true }}>
                {({ processing, errors }) => (
                    <div className="space-y-5">
                        <header>
                            <Link
                                href={clientes.index()}
                                className="text-text-tertiary hover:text-foreground inline-flex items-center gap-1 text-[12.5px]"
                            >
                                <ArrowLeft className="size-3.5" />
                                Clientes
                            </Link>
                            <h1 className="mt-2 text-[28px] font-bold tracking-[-0.03em]">
                                {editing ? 'Editar cliente' : 'Nuevo cliente'}
                            </h1>
                            <p className="text-muted-foreground mt-1 text-[13px]">
                                Los datos fiscales se usan tal cual en cuentas
                                de cobro y facturas.
                            </p>
                        </header>

                        <div className="flex flex-col gap-5 lg:flex-row">
                            <div className="min-w-0 flex-1 space-y-5">
                                <section className="bg-card rounded-card border-border shadow-card border p-5">
                                    <h2 className="text-[15px] font-semibold">
                                        Datos del cliente
                                    </h2>

                                    <div className="mt-4 grid gap-4 sm:grid-cols-2">
                                        <div className="grid gap-1.5">
                                            <Label>Tipo de persona</Label>
                                            <Segmented
                                                name="tipo_persona"
                                                value={values.tipo_persona}
                                                onChange={(tipo) =>
                                                    set({ tipo_persona: tipo })
                                                }
                                                options={[
                                                    { value: 'juridica', label: 'Jurídica' },
                                                    { value: 'natural', label: 'Natural' },
                                                ]}
                                            />
                                            <InputError message={errors.tipo_persona} />
                                        </div>

                                        <div className="grid gap-1.5">
                                            <Label htmlFor="tipo_documento_value_id">
                                                Tipo de documento
                                            </Label>
                                            <select
                                                id="tipo_documento_value_id"
                                                name="tipo_documento_value_id"
                                                value={values.tipo_documento_value_id ?? ''}
                                                onChange={(event) =>
                                                    set({ tipo_documento_value_id: Number(event.target.value) })
                                                }
                                                className="border-input bg-card h-9 rounded-md border px-3 text-[13px]"
                                            >
                                                <option value="">Selecciona…</option>
                                                {opciones.tipos_documento.map((opcion) => (
                                                    <option key={opcion.id} value={opcion.id}>
                                                        {opcion.name}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError message={errors.tipo_documento_value_id} />
                                        </div>

                                        <div className="grid gap-1.5">
                                            <Label htmlFor="documento">
                                                N.º documento
                                            </Label>
                                            <Input
                                                id="documento"
                                                name="documento"
                                                value={values.documento}
                                                onChange={(event) =>
                                                    set({ documento: event.target.value })
                                                }
                                                placeholder={PLACEHOLDERS[values.tipo_persona]}
                                                className="font-mono"
                                            />
                                            <InputError message={errors.documento} />
                                        </div>

                                        <div className="grid gap-1.5">
                                            <Label htmlFor="regimen_value_id">
                                                Régimen
                                            </Label>
                                            <select
                                                id="regimen_value_id"
                                                name="regimen_value_id"
                                                value={values.regimen_value_id ?? ''}
                                                onChange={(event) =>
                                                    set({ regimen_value_id: Number(event.target.value) })
                                                }
                                                className="border-input bg-card h-9 rounded-md border px-3 text-[13px]"
                                            >
                                                <option value="">Selecciona…</option>
                                                {opciones.regimenes.map((opcion) => (
                                                    <option key={opcion.id} value={opcion.id}>
                                                        {opcion.name}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError message={errors.regimen_value_id} />
                                        </div>

                                        <div className="grid gap-1.5 sm:col-span-2">
                                            <Label htmlFor="nombre">{nombreLabel}</Label>
                                            <Input
                                                id="nombre"
                                                name="nombre"
                                                value={values.nombre}
                                                onChange={(event) =>
                                                    set({ nombre: event.target.value })
                                                }
                                                placeholder={
                                                    esJuridica
                                                        ? 'Ej. Transportes Aurora SAS'
                                                        : 'Ej. Laura Gómez Restrepo'
                                                }
                                            />
                                            <InputError message={errors.nombre} />
                                        </div>

                                        <div className="grid gap-1.5">
                                            <Label htmlFor="tag">
                                                Tag
                                                <span className="text-text-tertiary font-normal">
                                                    {' '}· 3 caracteres
                                                </span>
                                            </Label>
                                            <div className="flex items-center gap-2">
                                                <span className="text-text-tertiary">#</span>
                                                <Input
                                                    id="tag"
                                                    name="tag"
                                                    value={values.tag}
                                                    maxLength={3}
                                                    onChange={(event) =>
                                                        set({ tag: event.target.value.toUpperCase() })
                                                    }
                                                    placeholder="ABC"
                                                    className="font-mono tracking-[0.18em] uppercase"
                                                />
                                                <span
                                                    className={cn(
                                                        'rounded-full px-2 py-0.5 text-[10.5px] font-semibold',
                                                        values.tag.trim() === ''
                                                            ? 'bg-surface-muted text-text-secondary'
                                                            : 'bg-[#e8f1ff] text-[#0a64d6]',
                                                    )}
                                                >
                                                    {values.tag.trim() === '' ? 'Auto' : 'Editado'}
                                                </span>
                                            </div>
                                            <p className="text-text-tertiary text-[11.5px]">
                                                {values.tag.trim().length === 3
                                                    ? `FAC-${values.tag.toUpperCase()}-0001 · COT-${values.tag.toUpperCase()}-0001`
                                                    : 'Se genera desde el nombre y se usa en la numeración de facturas y cotizaciones'}
                                            </p>
                                            <InputError message={errors.tag} />
                                        </div>

                                        <div className="grid gap-1.5">
                                            <Label>Estado</Label>
                                            <Segmented
                                                name="is_active"
                                                value={values.is_active ? '1' : '0'}
                                                onChange={(estado) =>
                                                    set({ is_active: estado === '1' })
                                                }
                                                options={[
                                                    { value: '1', label: 'Activo' },
                                                    { value: '0', label: 'Inactivo' },
                                                ]}
                                            />
                                        </div>

                                        <div className="grid gap-1.5">
                                            <Label htmlFor="ciudad">Ciudad</Label>
                                            <Input
                                                id="ciudad"
                                                name="ciudad"
                                                value={values.ciudad}
                                                onChange={(event) =>
                                                    set({ ciudad: event.target.value })
                                                }
                                                placeholder="Medellín"
                                            />
                                            <InputError message={errors.ciudad} />
                                        </div>

                                        <div className="grid gap-1.5">
                                            <Label htmlFor="departamento">
                                                Departamento
                                            </Label>
                                            <Input
                                                id="departamento"
                                                name="departamento"
                                                value={values.departamento}
                                                onChange={(event) =>
                                                    set({ departamento: event.target.value })
                                                }
                                                placeholder="Antioquia"
                                            />
                                            <InputError message={errors.departamento} />
                                        </div>

                                        <div className="grid gap-1.5 sm:col-span-2">
                                            <Label htmlFor="direccion">
                                                Dirección de facturación
                                            </Label>
                                            <Input
                                                id="direccion"
                                                name="direccion"
                                                value={values.direccion}
                                                onChange={(event) =>
                                                    set({ direccion: event.target.value })
                                                }
                                                placeholder="Calle 10 # 43-15, oficina 402"
                                            />
                                            <InputError message={errors.direccion} />
                                        </div>

                                        <div className="grid gap-1.5 sm:col-span-2">
                                            <Label htmlFor="notas">
                                                Notas internas
                                                <span className="text-text-tertiary font-normal">
                                                    {' '}· solo visible para el equipo
                                                </span>
                                            </Label>
                                            <textarea
                                                id="notas"
                                                name="notas"
                                                value={values.notas}
                                                onChange={(event) =>
                                                    set({ notas: event.target.value })
                                                }
                                                rows={4}
                                                placeholder="Ej. Requiere orden de compra antes de facturar."
                                                className="border-input bg-card rounded-md border px-3 py-2 text-[13px]"
                                            />
                                            <InputError message={errors.notas} />
                                        </div>
                                    </div>
                                </section>

                                <ContactosField
                                    contactos={values.contactos}
                                    errors={errors as Record<string, string>}
                                    onChange={(contactos) => set({ contactos })}
                                />
                            </div>

                            <aside className="w-full shrink-0 lg:sticky lg:top-20 lg:w-[290px] lg:self-start">
                                <div className="bg-card rounded-card border-border shadow-card border p-5">
                                    <div className="flex items-center gap-3">
                                        <ClienteAvatar
                                            iniciales={
                                                values.nombre.trim() === ''
                                                    ? '??'
                                                    : values.nombre
                                                          .trim()
                                                          .split(/\s+/)
                                                          .slice(0, 2)
                                                          .map((palabra) => palabra[0])
                                                          .join('')
                                                          .toUpperCase()
                                            }
                                        />
                                        <div className="min-w-0">
                                            <p className="truncate text-[13px] font-medium">
                                                {values.nombre.trim() || nombreLabel}
                                            </p>
                                            <p className="text-text-tertiary text-[11.5px]">
                                                Persona {esJuridica ? 'Jurídica' : 'Natural'}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="border-surface-muted mt-4 border-t pt-4">
                                        <p className="text-text-tertiary text-[11px] font-semibold tracking-[0.04em] uppercase">
                                            Campos obligatorios
                                        </p>
                                        <p className="mt-1 text-[12.5px]">
                                            {listos} de {checklist.length}
                                        </p>
                                        <div className="bg-surface-track mt-2 h-1 overflow-hidden rounded-full">
                                            <div
                                                className="bg-brand h-full transition-all"
                                                style={{
                                                    width: `${(listos / checklist.length) * 100}%`,
                                                }}
                                            />
                                        </div>

                                        <ul className="mt-3 space-y-1.5">
                                            {checklist.map((item) => (
                                                <li
                                                    key={item.label}
                                                    className={cn(
                                                        'flex items-center gap-1.5 text-[12.5px]',
                                                        item.done
                                                            ? 'text-[#1a7f37]'
                                                            : 'text-text-tertiary',
                                                    )}
                                                >
                                                    <Check
                                                        className={cn(
                                                            'size-3',
                                                            !item.done && 'opacity-30',
                                                        )}
                                                    />
                                                    {item.label}
                                                </li>
                                            ))}
                                        </ul>
                                    </div>

                                    <div className="mt-5 flex flex-col gap-2">
                                        <Button type="submit" disabled={processing}>
                                            {processing && <Spinner />}
                                            {editing ? 'Guardar cambios' : 'Guardar cliente'}
                                        </Button>
                                        <Button type="button" variant="secondary" asChild>
                                            <Link href={clientes.index()}>Cancelar</Link>
                                        </Button>
                                    </div>
                                </div>
                            </aside>
                        </div>
                    </div>
                )}
            </Form>
        </>
    );
}
