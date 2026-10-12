import { Plus, X } from 'lucide-react';
import { Input } from '@/components/ui/input';
import type { ClienteContacto } from '@/features/clientes/types';
import { cn } from '@/lib/utils';

function initials(nombre: string): string {
    const words = nombre.trim().split(/\s+/).filter(Boolean);

    if (words.length === 0) {
        return '··';
    }

    if (words.length === 1) {
        return words[0].slice(0, 2).toUpperCase();
    }

    return (words[0][0] + words[1][0]).toUpperCase();
}

function ContactoInput({
    id,
    name,
    label,
    value,
    onChange,
    placeholder,
    type,
    mono,
    error,
}: {
    id: string;
    name: string;
    label: string;
    value: string;
    onChange: (value: string) => void;
    placeholder: string;
    type?: string;
    mono?: boolean;
    error?: string;
}) {
    return (
        <div className="flex min-w-0 flex-col gap-1.5">
            <label
                htmlFor={id}
                className="text-text-secondary text-[12px] font-medium"
            >
                {label}
            </label>
            <Input
                id={id}
                name={name}
                type={type}
                value={value}
                onChange={(event) => onChange(event.target.value)}
                placeholder={placeholder}
                className={cn(mono && 'font-mono text-[13px]')}
            />
            {error && <p className="text-destructive text-[12px]">{error}</p>}
        </div>
    );
}

/**
 * The contacts repeater.
 *
 * Exactly one contact is principal and at least one must exist — the database
 * enforces both with a deferred trigger, and this keeps the form from ever
 * submitting a shape it would reject.
 */
export function ContactosField({
    contactos,
    errors,
    onChange,
}: {
    contactos: ClienteContacto[];
    errors: Record<string, string>;
    onChange: (contactos: ClienteContacto[]) => void;
}) {
    const update = (index: number, cambios: Partial<ClienteContacto>): void => {
        onChange(
            contactos.map((contacto, actual) =>
                actual === index ? { ...contacto, ...cambios } : contacto,
            ),
        );
    };

    const markPrincipal = (index: number): void => {
        onChange(
            contactos.map((contacto, actual) => ({
                ...contacto,
                is_principal: actual === index,
            })),
        );
    };

    const remove = (index: number): void => {
        const quedan = contactos.filter((_, actual) => actual !== index);

        // Removing the principal would leave the client without one, so the
        // first survivor takes over rather than the form going invalid.
        if (!quedan.some((contacto) => contacto.is_principal) && quedan[0]) {
            quedan[0] = { ...quedan[0], is_principal: true };
        }

        onChange(quedan);
    };

    const add = (): void => {
        onChange([
            ...contactos,
            {
                id: null,
                nombre: '',
                cargo: '',
                email: '',
                telefono: '',
                is_principal: contactos.length === 0,
            },
        ]);
    };

    const solo = contactos.length === 1;

    return (
        <section className="bg-card rounded-card border-border shadow-card overflow-hidden border">
            <header className="flex flex-wrap items-baseline justify-between gap-3 px-[22px] pt-[18px] pb-3.5">
                <div className="flex items-baseline gap-2.5">
                    <span className="text-[13px] font-semibold tracking-[-0.01em]">
                        Contactos
                    </span>
                    <span className="text-text-tertiary text-[11.5px]">
                        {contactos.length}{' '}
                        {contactos.length === 1 ? 'contacto' : 'contactos'}
                    </span>
                </div>
                <span className="text-text-tertiary text-[11.5px]">
                    Mínimo uno. El principal recibe cotizaciones y cuentas de
                    cobro.
                </span>
            </header>

            {errors.contactos && (
                <p className="text-destructive px-[22px] pb-2 text-[12.5px]">
                    {errors.contactos}
                </p>
            )}

            <div className="flex flex-col gap-2.5 px-[22px] pb-1.5">
                {contactos.map((contacto, index) => (
                    <article
                        key={index}
                        className={cn(
                            'flex flex-col gap-2.5 rounded-xl border p-3.5 pt-3 transition-colors',
                            contacto.is_principal
                                ? 'border-brand-border bg-[#fffaf5]'
                                : 'border-border bg-card',
                        )}
                    >
                        <input
                            type="hidden"
                            name={`contactos[${index}][is_principal]`}
                            value={contacto.is_principal ? '1' : '0'}
                        />

                        <div className="flex items-center justify-between gap-2.5">
                            <div className="flex min-w-0 items-center gap-2.5">
                                <span
                                    className={cn(
                                        'flex size-[26px] shrink-0 items-center justify-center rounded-full text-[10.5px] font-bold',
                                        contacto.is_principal
                                            ? 'bg-linear-[160deg,#fdba74,#f97316] text-white'
                                            : 'bg-surface-muted text-text-secondary',
                                    )}
                                >
                                    {initials(contacto.nombre)}
                                </span>
                                <span className="truncate text-[12.5px] font-semibold">
                                    Contacto {index + 1}
                                </span>
                            </div>

                            <div className="flex items-center gap-1">
                                <button
                                    type="button"
                                    onClick={() => markPrincipal(index)}
                                    disabled={contacto.is_principal}
                                    className={cn(
                                        'flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11.5px] font-semibold transition-colors',
                                        contacto.is_principal
                                            ? 'border-brand-border bg-brand-surface text-brand-text'
                                            : 'border-input text-text-secondary hover:bg-surface-subtle',
                                    )}
                                >
                                    <span
                                        className={cn(
                                            'flex size-2.5 items-center justify-center rounded-full border-[1.5px] bg-card',
                                            contacto.is_principal
                                                ? 'border-brand'
                                                : 'border-[#c7c7cc]',
                                        )}
                                    >
                                        {contacto.is_principal && (
                                            <span className="bg-brand size-1 rounded-full" />
                                        )}
                                    </span>
                                    {contacto.is_principal
                                        ? 'Principal'
                                        : 'Marcar principal'}
                                </button>

                                <button
                                    type="button"
                                    onClick={() => remove(index)}
                                    disabled={solo}
                                    title={
                                        solo
                                            ? 'Se requiere al menos un contacto'
                                            : 'Quitar contacto'
                                    }
                                    aria-label={
                                        solo
                                            ? 'Se requiere al menos un contacto'
                                            : 'Quitar contacto'
                                    }
                                    className="text-text-tertiary hover:text-destructive flex size-7 items-center justify-center rounded-lg transition-colors hover:bg-[#ffeceb] disabled:pointer-events-none disabled:opacity-30"
                                >
                                    <X className="size-3.5" />
                                </button>
                            </div>
                        </div>

                        <div className="grid grid-cols-[repeat(auto-fill,minmax(190px,1fr))] gap-x-3 gap-y-2.5">
                            <ContactoInput
                                id={`contacto-${index}-nombre`}
                                name={`contactos[${index}][nombre]`}
                                label="Nombre"
                                value={contacto.nombre}
                                onChange={(nombre) => update(index, { nombre })}
                                placeholder="Nombre y apellido"
                                error={errors[`contactos.${index}.nombre`]}
                            />
                            <ContactoInput
                                id={`contacto-${index}-cargo`}
                                name={`contactos[${index}][cargo]`}
                                label="Cargo"
                                value={contacto.cargo}
                                onChange={(cargo) => update(index, { cargo })}
                                placeholder="Ej. Gerente de tecnología"
                            />
                            <ContactoInput
                                id={`contacto-${index}-email`}
                                name={`contactos[${index}][email]`}
                                label="Email"
                                type="email"
                                value={contacto.email}
                                onChange={(email) => update(index, { email })}
                                placeholder="contacto@empresa.co"
                                error={errors[`contactos.${index}.email`]}
                            />
                            <ContactoInput
                                id={`contacto-${index}-telefono`}
                                name={`contactos[${index}][telefono]`}
                                label="Teléfono"
                                value={contacto.telefono}
                                onChange={(telefono) =>
                                    update(index, { telefono })
                                }
                                placeholder="+57 300 000 0000"
                                mono
                            />
                        </div>
                    </article>
                ))}
            </div>

            <div className="px-[22px] pt-2 pb-[18px]">
                <button
                    type="button"
                    onClick={add}
                    className="text-brand-text flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-[rgba(249,115,22,.45)] bg-[#fffaf5] p-2.5 text-[12.5px] font-medium transition-colors hover:border-solid hover:bg-brand-surface"
                >
                    <Plus className="size-3.5" />
                    Agregar contacto
                </button>
            </div>
        </section>
    );
}
