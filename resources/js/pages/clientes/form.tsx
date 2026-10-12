import { Form, Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';
import ClientesController from '@/actions/App/Http/Controllers/ClientesController';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Field } from '@/features/clientes/field';
import { ContactosField } from '@/features/clientes/contactos-field';
import { NotasEditor } from '@/features/clientes/notas-editor';
import { ResumenCard } from '@/features/clientes/resumen-card';
import { Segmented } from '@/features/clientes/segmented';
import { TagBadge, TagField } from '@/features/clientes/tag-field';
import type {
    ClienteFormValues,
    ClienteOptions,
} from '@/features/clientes/types';
import clientes from '@/routes/clientes';
import { dashboard } from '@/routes';

type Props = {
    cliente: ClienteFormValues;
    opciones: ClienteOptions;
};

const DOCUMENTO_PLACEHOLDER: Record<string, string> = {
    juridica: '900.000.000-0',
    natural: '1.000.000.000',
};

function iniciales(nombre: string): string {
    const words = nombre.trim().split(/\s+/).filter(Boolean);

    if (words.length === 0) {
        return '··';
    }

    if (words.length === 1) {
        return words[0].slice(0, 2).toUpperCase();
    }

    return (words[0][0] + words[1][0]).toUpperCase();
}

/**
 * Alta y edición de un cliente.
 *
 * Two columns, as the prototype lays it out: the form on the left and a sticky
 * summary on the right showing what the client will look like once saved and
 * what is still missing.
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

    const nombreDe = (
        lista: ClienteOptions['tipos_documento'],
        id: number | null,
    ): string => lista.find((opcion) => opcion.id === id)?.name ?? '';

    const checklist = [
        { label: nombreLabel, done: values.nombre.trim() !== '' },
        { label: 'Número de documento', done: values.documento.trim() !== '' },
        {
            label: 'Ciudad y departamento',
            done:
                values.ciudad.trim() !== '' &&
                values.departamento.trim() !== '',
        },
        {
            label: 'Contacto principal con email',
            done: values.contactos.some(
                (contacto) =>
                    contacto.is_principal && contacto.email.trim() !== '',
            ),
        },
    ];

    /**
     * Switching the kind of person re-picks the document type and régimen the
     * prototype preselects for it, instead of leaving a company holding a
     * cédula.
     */
    const cambiarPersona = (tipo: string): void => {
        const documentoKey = tipo === 'juridica' ? 'NIT' : 'Cédula de ciudadanía';
        const regimenKey =
            tipo === 'juridica' ? 'Responsable de IVA' : 'No responsable de IVA';

        set({
            tipo_persona: tipo,
            tipo_documento_value_id:
                opciones.tipos_documento.find(
                    (opcion) => opcion.name === documentoKey,
                )?.id ?? values.tipo_documento_value_id,
            regimen_value_id:
                opciones.regimenes.find((opcion) => opcion.name === regimenKey)
                    ?.id ?? values.regimen_value_id,
        });
    };

    return (
        <>
            <Head title={editing ? 'Editar cliente' : 'Nuevo cliente'} />

            <Form {...action} options={{ preserveScroll: true }}>
                {({ processing, errors }) => (
                    <div className="flex flex-wrap items-start gap-5">
                        <div className="flex min-w-0 flex-[1_1_460px] flex-col gap-4">
                            <header>
                                <Link
                                    href={clientes.index()}
                                    className="text-muted-foreground hover:text-foreground mb-2.5 inline-flex items-center gap-1 text-[12.5px] transition-colors"
                                >
                                    <ArrowLeft className="size-3.5" />
                                    Clientes
                                </Link>
                                <h1 className="text-[28px] font-bold tracking-[-0.03em]">
                                    {editing ? 'Editar cliente' : 'Nuevo cliente'}
                                </h1>
                                <p className="text-muted-foreground mt-1 text-[13.5px]">
                                    Los datos fiscales se usan tal cual en
                                    cuentas de cobro y facturas.
                                </p>
                            </header>

                            <section className="bg-card rounded-card border-border shadow-card flex flex-col gap-4 border px-[22px] pt-5 pb-[22px]">
                                <h2 className="text-[13px] font-semibold tracking-[-0.01em]">
                                    Datos del cliente
                                </h2>

                                <div className="grid grid-cols-[repeat(auto-fill,minmax(210px,1fr))] gap-x-4 gap-y-3.5">
                                    <Field
                                        label="Tipo de persona"
                                        error={errors.tipo_persona}
                                    >
                                        <Segmented
                                            name="tipo_persona"
                                            value={values.tipo_persona}
                                            onChange={cambiarPersona}
                                            options={[
                                                { value: 'juridica', label: 'Jurídica' },
                                                { value: 'natural', label: 'Natural' },
                                            ]}
                                        />
                                    </Field>

                                    <Field
                                        label="Tipo de documento"
                                        htmlFor="tipo_documento_value_id"
                                        error={errors.tipo_documento_value_id}
                                    >
                                        <NativeSelect
                                            id="tipo_documento_value_id"
                                            name="tipo_documento_value_id"
                                            value={values.tipo_documento_value_id ?? ''}
                                            onChange={(event) =>
                                                set({
                                                    tipo_documento_value_id:
                                                        Number(event.target.value),
                                                })
                                            }
                                        >
                                            <option value="">Selecciona…</option>
                                            {opciones.tipos_documento.map((opcion) => (
                                                <option key={opcion.id} value={opcion.id}>
                                                    {opcion.name}
                                                </option>
                                            ))}
                                        </NativeSelect>
                                    </Field>

                                    <Field
                                        label="N.º documento"
                                        htmlFor="documento"
                                        error={errors.documento}
                                    >
                                        <Input
                                            id="documento"
                                            name="documento"
                                            value={values.documento}
                                            onChange={(event) =>
                                                set({ documento: event.target.value })
                                            }
                                            placeholder={DOCUMENTO_PLACEHOLDER[values.tipo_persona]}
                                            className="font-mono"
                                        />
                                    </Field>

                                    <Field
                                        label="Régimen"
                                        htmlFor="regimen_value_id"
                                        error={errors.regimen_value_id}
                                    >
                                        <NativeSelect
                                            id="regimen_value_id"
                                            name="regimen_value_id"
                                            value={values.regimen_value_id ?? ''}
                                            onChange={(event) =>
                                                set({
                                                    regimen_value_id: Number(event.target.value),
                                                })
                                            }
                                        >
                                            <option value="">Selecciona…</option>
                                            {opciones.regimenes.map((opcion) => (
                                                <option key={opcion.id} value={opcion.id}>
                                                    {opcion.name}
                                                </option>
                                            ))}
                                        </NativeSelect>
                                    </Field>

                                    <Field
                                        label={nombreLabel}
                                        htmlFor="nombre"
                                        span={2}
                                        error={errors.nombre}
                                    >
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
                                    </Field>

                                    <Field
                                        label="Tag"
                                        htmlFor="tag"
                                        aside={<TagBadge value={values.tag} />}
                                        error={errors.tag}
                                    >
                                        <TagField
                                            value={values.tag}
                                            onChange={(tag) => set({ tag })}
                                            onReset={() => set({ tag: '' })}
                                        />
                                    </Field>

                                    <Field label="Ciudad" htmlFor="ciudad" error={errors.ciudad}>
                                        <Input
                                            id="ciudad"
                                            name="ciudad"
                                            value={values.ciudad}
                                            onChange={(event) =>
                                                set({ ciudad: event.target.value })
                                            }
                                            placeholder="Medellín"
                                        />
                                    </Field>

                                    <Field
                                        label="Departamento"
                                        htmlFor="departamento"
                                        error={errors.departamento}
                                    >
                                        <Input
                                            id="departamento"
                                            name="departamento"
                                            value={values.departamento}
                                            onChange={(event) =>
                                                set({ departamento: event.target.value })
                                            }
                                            placeholder="Antioquia"
                                        />
                                    </Field>

                                    <Field label="Estado">
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
                                    </Field>

                                    <Field
                                        label="Dirección de facturación"
                                        htmlFor="direccion"
                                        span="full"
                                        error={errors.direccion}
                                    >
                                        <Input
                                            id="direccion"
                                            name="direccion"
                                            value={values.direccion}
                                            onChange={(event) =>
                                                set({ direccion: event.target.value })
                                            }
                                            placeholder="Calle 10 # 43-15, oficina 402"
                                        />
                                    </Field>

                                    <Field
                                        label="Notas internas"
                                        hint="Solo visible para el equipo · admite Markdown"
                                        span="full"
                                        error={errors.notas}
                                    >
                                        <NotasEditor
                                            value={values.notas}
                                            onChange={(notas) => set({ notas })}
                                        />
                                    </Field>
                                </div>
                            </section>

                            <ContactosField
                                contactos={values.contactos}
                                errors={errors as Record<string, string>}
                                onChange={(contactos) => set({ contactos })}
                            />
                        </div>

                        <aside className="flex w-full max-w-full flex-[1_1_260px] flex-col gap-3.5 lg:max-w-[320px] lg:sticky lg:top-[84px]">
                            <ResumenCard
                                values={values}
                                tipoDocumento={nombreDe(
                                    opciones.tipos_documento,
                                    values.tipo_documento_value_id,
                                )}
                                regimen={nombreDe(
                                    opciones.regimenes,
                                    values.regimen_value_id,
                                )}
                                iniciales={iniciales(values.nombre)}
                                nombreLabel={nombreLabel}
                                checklist={checklist}
                                processing={processing}
                                editing={editing}
                                onCancel={() => router.visit(clientes.index().url)}
                            />
                        </aside>
                    </div>
                )}
            </Form>
        </>
    );
}
