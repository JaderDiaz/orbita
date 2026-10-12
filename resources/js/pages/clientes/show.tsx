import { Head, Link, router, setLayoutProps } from '@inertiajs/react';
import {
    Archive,
    Check,
    Copy,
    MoreHorizontal,
    Pencil,
    RotateCcw,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import ClientesController from '@/actions/App/Http/Controllers/ClientesController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { ClienteAvatar } from '@/features/clientes/cliente-avatar';
import { EstadoBadge } from '@/features/clientes/estado-badge';
import type { ClienteDetail } from '@/features/clientes/types';
import { useClipboard } from '@/hooks/use-clipboard';
import clientes from '@/routes/clientes';
import { dashboard } from '@/routes';

type Props = { cliente: ClienteDetail };

function FichaRow({ label, value }: { label: string; value: string | null }) {
    const [copiedText, copy] = useClipboard();

    if (!value) {
        return null;
    }

    const copied = copiedText === value;

    return (
        <div className="border-surface-muted flex items-start justify-between gap-3 border-b py-2 last:border-0">
            <span className="text-text-tertiary shrink-0 text-[11.5px]">
                {label}
            </span>
            <span className="flex items-center gap-1.5 text-right text-[12.5px]">
                {value}
                <button
                    type="button"
                    onClick={() => void copy(value)}
                    title="Copiar"
                    aria-label={`Copiar ${label}`}
                    className="text-text-tertiary hover:text-foreground"
                >
                    {copied ? (
                        <Check className="size-3" />
                    ) : (
                        <Copy className="size-3" />
                    )}
                </button>
            </span>
        </div>
    );
}

/**
 * Ficha del cliente.
 *
 * Deliberately without the prototype's four KPI cards and five tabs: Proyectos,
 * Cotizaciones, Facturas and Documentos do not exist yet, and a tab that is
 * always empty tells the reader nothing.
 */
export default function ClienteShow({ cliente }: Props) {
    const [confirming, setConfirming] = useState(false);

    setLayoutProps({
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Clientes', href: clientes.index() },
            { title: cliente.nombre, href: clientes.show(cliente.id) },
        ],
    });

    const enUso = cliente.usos > 0;

    const toggle = (): void =>
        router.visit(ClientesController.toggle.url({ cliente: cliente.id }), {
            method: 'post',
            preserveScroll: true,
        });

    const destroy = (): void =>
        router.visit(ClientesController.destroy.url({ cliente: cliente.id }), {
            method: 'delete',
        });

    return (
        <>
            <Head title={cliente.nombre} />

            <div className="space-y-5">
                <header className="rounded-card shell-glow border-border shadow-card overflow-hidden border bg-[#1c1c1e]">
                    <div className="flex flex-wrap items-center gap-4 p-6">
                        <ClienteAvatar iniciales={cliente.iniciales} size="lg" />

                        <div className="min-w-0 flex-1">
                            <div className="flex flex-wrap items-center gap-2">
                                <h1 className="text-[24px] font-bold tracking-[-0.03em] text-white">
                                    {cliente.nombre}
                                </h1>
                                <span className="rounded bg-black/40 px-1.5 py-0.5 font-mono text-[11px] tracking-[0.12em] text-white">
                                    #{cliente.tag}
                                </span>
                                <EstadoBadge activo={cliente.is_active} />
                            </div>
                            <p className="mt-1 text-[12.5px] text-white/65">
                                {[
                                    `${cliente.tipo_documento} ${cliente.documento}`,
                                    cliente.regimen,
                                    `${cliente.ciudad}, ${cliente.departamento}`,
                                    cliente.desde && `Cliente desde ${cliente.desde}`,
                                ]
                                    .filter(Boolean)
                                    .join(' · ')}
                            </p>
                        </div>

                        <div className="flex items-center gap-2">
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <button
                                        type="button"
                                        title="Más acciones"
                                        aria-label="Más acciones"
                                        className="flex size-8 items-center justify-center rounded-md text-white/70 transition-colors hover:bg-white/10 hover:text-white"
                                    >
                                        <MoreHorizontal className="size-4" />
                                    </button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem onClick={toggle}>
                                        {cliente.is_active ? (
                                            <>
                                                <Archive className="size-3.5" />
                                                Marcar como inactivo
                                            </>
                                        ) : (
                                            <>
                                                <RotateCcw className="size-3.5" />
                                                Reactivar cliente
                                            </>
                                        )}
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem
                                        variant="destructive"
                                        onClick={() => setConfirming(true)}
                                    >
                                        <Trash2 className="size-3.5" />
                                        Eliminar cliente
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>

                            <Button asChild variant="secondary">
                                <Link href={clientes.edit(cliente.id)}>
                                    <Pencil className="size-3.5" />
                                    Editar
                                </Link>
                            </Button>
                        </div>
                    </div>
                </header>

                <div className="flex flex-col gap-5 lg:flex-row">
                    <div className="min-w-0 flex-1 space-y-5">
                        <section className="bg-card rounded-card border-border shadow-card border p-5">
                            <h2 className="text-[15px] font-semibold">
                                Contactos
                            </h2>
                            <p className="text-text-tertiary mt-0.5 text-[12.5px]">
                                {cliente.contactos.length}{' '}
                                {cliente.contactos.length === 1
                                    ? 'contacto'
                                    : 'contactos'}
                            </p>

                            <ul className="mt-4 space-y-3">
                                {cliente.contactos.map((contacto) => (
                                    <li
                                        key={contacto.id}
                                        className="border-border flex flex-wrap items-start justify-between gap-3 rounded-md border p-3"
                                    >
                                        <div>
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span className="text-[13px] font-medium">
                                                    {contacto.nombre}
                                                </span>
                                                {contacto.is_principal && (
                                                    <span className="bg-brand-surface text-brand-text rounded-full px-1.5 py-px text-[10px] font-semibold">
                                                        Principal
                                                    </span>
                                                )}
                                            </div>
                                            {contacto.cargo && (
                                                <p className="text-text-tertiary text-[11.5px]">
                                                    {contacto.cargo}
                                                </p>
                                            )}
                                        </div>
                                        <div className="text-right text-[12.5px]">
                                            <a
                                                href={`mailto:${contacto.email}`}
                                                className="text-brand-text hover:underline"
                                            >
                                                {contacto.email}
                                            </a>
                                            {contacto.telefono && (
                                                <p className="tabular text-text-tertiary text-[11.5px]">
                                                    {contacto.telefono}
                                                </p>
                                            )}
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        </section>

                        {cliente.notas && (
                            <section className="bg-card rounded-card border-border shadow-card border p-5">
                                <div className="flex items-baseline justify-between gap-2">
                                    <h2 className="text-[15px] font-semibold">
                                        Notas internas
                                    </h2>
                                    <span className="text-text-tertiary text-[11px] font-semibold tracking-[0.04em] uppercase">
                                        Solo equipo
                                    </span>
                                </div>
                                {/* Sanitized on the server by SafeMarkdown:
                                    escaped first, with link schemes allowlisted.
                                    The prototype emitted raw hrefs here. */}
                                <div
                                    className="markdown text-muted-foreground mt-2 text-[13px] leading-relaxed"
                                    dangerouslySetInnerHTML={{
                                        __html: cliente.notas_html,
                                    }}
                                />
                            </section>
                        )}
                    </div>

                    <aside className="w-full shrink-0 lg:w-[300px]">
                        <section className="bg-card rounded-card border-border shadow-card border p-5">
                            <h2 className="text-[15px] font-semibold">
                                Ficha fiscal
                            </h2>
                            <div className="mt-3">
                                <FichaRow
                                    label={cliente.tipo_documento}
                                    value={cliente.documento}
                                />
                                <FichaRow label="Régimen" value={cliente.regimen} />
                                <FichaRow label="Tipo de persona" value={cliente.tipo_persona_label} />
                                <FichaRow
                                    label="Tag de numeración"
                                    value={`#${cliente.tag} · ${cliente.numeracion.factura}`}
                                />
                                <FichaRow
                                    label="Dirección de facturación"
                                    value={cliente.direccion}
                                />
                                <FichaRow
                                    label="Ciudad"
                                    value={`${cliente.ciudad}, ${cliente.departamento}`}
                                />
                                <FichaRow label="Cliente desde" value={cliente.desde} />
                            </div>
                        </section>
                    </aside>
                </div>
            </div>

            <Dialog open={confirming} onOpenChange={setConfirming}>
                <DialogContent>
                    {enUso ? (
                        <>
                            <DialogTitle>
                                {cliente.nombre} no se puede eliminar
                            </DialogTitle>
                            <DialogDescription>
                                Tiene historial asociado. Para conservar la
                                trazabilidad contable, archívalo como inactivo:
                                saldrá de los listados activos y su historial
                                queda intacto.
                            </DialogDescription>
                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary">Cancelar</Button>
                                </DialogClose>
                                <Button
                                    onClick={() => {
                                        setConfirming(false);
                                        toggle();
                                    }}
                                >
                                    Archivar como inactivo
                                </Button>
                            </DialogFooter>
                        </>
                    ) : (
                        <>
                            <DialogTitle>
                                ¿Eliminar {cliente.nombre}?
                            </DialogTitle>
                            <DialogDescription>
                                Se eliminarán su ficha, contactos y notas. Esta
                                acción no se puede deshacer.
                            </DialogDescription>
                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary">Cancelar</Button>
                                </DialogClose>
                                <Button variant="destructive" onClick={destroy}>
                                    Eliminar
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}
