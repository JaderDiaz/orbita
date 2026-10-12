import { Plus, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ClienteContacto } from '@/features/clientes/types';
import { cn } from '@/lib/utils';

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

    return (
        <section className="bg-card rounded-card border-border shadow-card border p-5">
            <header className="flex flex-wrap items-baseline justify-between gap-2">
                <h2 className="text-[15px] font-semibold">Contactos</h2>
                <span className="text-text-tertiary text-[12.5px]">
                    {contactos.length}{' '}
                    {contactos.length === 1 ? 'contacto' : 'contactos'}
                </span>
            </header>
            <p className="text-muted-foreground mt-1 text-[12.5px]">
                Mínimo uno. El principal recibe cotizaciones y cuentas de cobro.
            </p>

            {errors.contactos && (
                <p className="text-destructive mt-2 text-[12.5px]">
                    {errors.contactos}
                </p>
            )}

            <div className="mt-4 space-y-3">
                {contactos.map((contacto, index) => (
                    <article
                        key={index}
                        className={cn(
                            'rounded-md border p-4',
                            contacto.is_principal
                                ? 'border-brand-border bg-brand-surface/40'
                                : 'border-border',
                        )}
                    >
                        <input
                            type="hidden"
                            name={`contactos[${index}][is_principal]`}
                            value={contacto.is_principal ? '1' : '0'}
                        />

                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <span className="text-[12.5px] font-medium">
                                Contacto {index + 1}
                            </span>

                            <div className="flex items-center gap-2">
                                <button
                                    type="button"
                                    onClick={() => markPrincipal(index)}
                                    disabled={contacto.is_principal}
                                    className={cn(
                                        'rounded-full px-2.5 py-0.5 text-[11px] font-semibold transition-colors',
                                        contacto.is_principal
                                            ? 'bg-brand text-white'
                                            : 'text-text-secondary hover:bg-surface-muted border-input border',
                                    )}
                                >
                                    {contacto.is_principal
                                        ? 'Principal'
                                        : 'Marcar principal'}
                                </button>

                                <button
                                    type="button"
                                    onClick={() => remove(index)}
                                    disabled={contactos.length === 1}
                                    title={
                                        contactos.length === 1
                                            ? 'Se requiere al menos un contacto'
                                            : 'Quitar contacto'
                                    }
                                    aria-label={
                                        contactos.length === 1
                                            ? 'Se requiere al menos un contacto'
                                            : 'Quitar contacto'
                                    }
                                    className="text-text-tertiary hover:text-destructive flex size-6 items-center justify-center rounded-md transition-colors disabled:pointer-events-none disabled:opacity-30"
                                >
                                    <X className="size-3.5" />
                                </button>
                            </div>
                        </div>

                        <div className="mt-3 grid gap-3 sm:grid-cols-2">
                            <div className="grid gap-1.5">
                                <Label htmlFor={`contacto-${index}-nombre`}>
                                    Nombre
                                </Label>
                                <Input
                                    id={`contacto-${index}-nombre`}
                                    name={`contactos[${index}][nombre]`}
                                    value={contacto.nombre}
                                    onChange={(event) =>
                                        update(index, {
                                            nombre: event.target.value,
                                        })
                                    }
                                    placeholder="Nombre y apellido"
                                />
                                {errors[`contactos.${index}.nombre`] && (
                                    <p className="text-destructive text-[12px]">
                                        {errors[`contactos.${index}.nombre`]}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor={`contacto-${index}-cargo`}>
                                    Cargo
                                </Label>
                                <Input
                                    id={`contacto-${index}-cargo`}
                                    name={`contactos[${index}][cargo]`}
                                    value={contacto.cargo}
                                    onChange={(event) =>
                                        update(index, {
                                            cargo: event.target.value,
                                        })
                                    }
                                    placeholder="Ej. Gerente de tecnología"
                                />
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor={`contacto-${index}-email`}>
                                    Email
                                </Label>
                                <Input
                                    id={`contacto-${index}-email`}
                                    name={`contactos[${index}][email]`}
                                    type="email"
                                    value={contacto.email}
                                    onChange={(event) =>
                                        update(index, {
                                            email: event.target.value,
                                        })
                                    }
                                    placeholder="contacto@empresa.co"
                                />
                                {errors[`contactos.${index}.email`] && (
                                    <p className="text-destructive text-[12px]">
                                        {errors[`contactos.${index}.email`]}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor={`contacto-${index}-telefono`}>
                                    Teléfono
                                </Label>
                                <Input
                                    id={`contacto-${index}-telefono`}
                                    name={`contactos[${index}][telefono]`}
                                    value={contacto.telefono}
                                    onChange={(event) =>
                                        update(index, {
                                            telefono: event.target.value,
                                        })
                                    }
                                    placeholder="+57 300 000 0000"
                                    className="font-mono"
                                />
                            </div>
                        </div>
                    </article>
                ))}
            </div>

            <Button
                type="button"
                variant="secondary"
                onClick={add}
                className="mt-3"
            >
                <Plus className="size-3.5" />
                Agregar contacto
            </Button>
        </section>
    );
}
