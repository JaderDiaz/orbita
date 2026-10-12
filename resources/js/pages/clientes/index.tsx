import { Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { Plus, Search, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { ClienteAvatar } from '@/features/clientes/cliente-avatar';
import { EstadoBadge } from '@/features/clientes/estado-badge';
import {
    paginationSummary,
    type ClienteRow,
    type Paginator,
} from '@/features/clientes/types';
import { cn } from '@/lib/utils';
import clientes from '@/routes/clientes';
import { dashboard } from '@/routes';

type Filtros = { buscar: string; estado: string; filas: number };

type Props = {
    clientes: Paginator<ClienteRow>;
    filtros: Filtros;
    resumen: { total: number; activos: number };
    opciones_filas: number[];
};

const ESTADOS = [
    { key: 'todos', label: 'Todos' },
    { key: 'activos', label: 'Activos' },
    { key: 'inactivos', label: 'Inactivos' },
];

/**
 * Clientes.
 *
 * Search, filtering and pagination all round-trip to the server: the list grows
 * without bound, so filtering in the browser would stop working exactly when
 * the agency has enough clients to need it.
 */
export default function ClientesIndex({
    clientes: paginator,
    filtros,
    resumen,
    opciones_filas,
}: Props) {
    const [buscar, setBuscar] = useState(filtros.buscar);
    const first = useRef(true);

    setLayoutProps({
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Clientes', href: clientes.index() },
        ],
    });

    // Debounced so typing does not fire a request per keystroke.
    useEffect(() => {
        if (first.current) {
            first.current = false;

            return;
        }

        const timer = setTimeout(() => {
            router.get(
                clientes.index().url,
                { ...filtros, buscar, page: 1 },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [buscar]);

    const apply = (cambios: Partial<Filtros>): void => {
        router.get(
            clientes.index().url,
            { ...filtros, buscar, ...cambios, page: 1 },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const subtitulo = useMemo(() => {
        const empresas = `${resumen.total} ${resumen.total === 1 ? 'empresa' : 'empresas'}`;
        const activas = `${resumen.activos} ${resumen.activos === 1 ? 'activa' : 'activas'}`;

        return `${empresas} · ${activas}`;
    }, [resumen]);

    return (
        <>
            <Head title="Clientes" />

            <div className="space-y-5">
                <header className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-[28px] font-bold tracking-[-0.03em]">
                            Clientes
                        </h1>
                        <p className="text-muted-foreground mt-1 text-[13px]">
                            {subtitulo}
                        </p>
                    </div>

                    <Button asChild>
                        <Link href={clientes.create()}>
                            <Plus className="size-3.5" />
                            Nuevo cliente
                        </Link>
                    </Button>
                </header>

                <div className="flex flex-wrap items-center gap-3">
                    <div className="flex items-center gap-1.5">
                        {ESTADOS.map((estado) => (
                            <button
                                key={estado.key}
                                type="button"
                                onClick={() => apply({ estado: estado.key })}
                                aria-pressed={filtros.estado === estado.key}
                                className={cn(
                                    'rounded-full px-3 py-1 text-[12.5px] transition-colors',
                                    filtros.estado === estado.key
                                        ? 'bg-foreground text-background font-medium'
                                        : 'text-text-secondary hover:bg-surface-subtle',
                                )}
                            >
                                {estado.label}
                            </button>
                        ))}
                    </div>

                    <div className="relative ml-auto w-full sm:w-[320px]">
                        <Search className="text-text-tertiary pointer-events-none absolute top-1/2 left-3 size-3.5 -translate-y-1/2" />
                        <Input
                            value={buscar}
                            onChange={(event) => setBuscar(event.target.value)}
                            placeholder="Buscar por empresa, NIT o contacto"
                            aria-label="Buscar por empresa, NIT o contacto"
                            className="rounded-full pr-9 pl-9"
                        />
                        {buscar !== '' && (
                            <button
                                type="button"
                                onClick={() => setBuscar('')}
                                aria-label="Limpiar la búsqueda"
                                className="text-text-tertiary hover:text-foreground absolute top-1/2 right-3 -translate-y-1/2"
                            >
                                <X className="size-3.5" />
                            </button>
                        )}
                    </div>
                </div>

                <section className="bg-card rounded-card border-border shadow-card border">
                    {paginator.data.length === 0 ? (
                        <div className="px-4 py-12 text-center">
                            <p className="text-[14px] font-medium">
                                Sin resultados
                            </p>
                            <p className="text-text-tertiary mt-1 text-[13px]">
                                Ningún cliente coincide con la búsqueda.
                            </p>
                        </div>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow className="hover:bg-transparent">
                                    <TableHead>Empresa</TableHead>
                                    <TableHead className="w-[170px]">
                                        NIT
                                    </TableHead>
                                    <TableHead className="w-[260px]">
                                        Contacto
                                    </TableHead>
                                    <TableHead className="w-[110px] text-right">
                                        Estado
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {paginator.data.map((cliente) => (
                                    <TableRow
                                        key={cliente.id}
                                        className={cn(
                                            'cursor-pointer',
                                            !cliente.is_active && 'opacity-55',
                                        )}
                                        onClick={() =>
                                            router.visit(
                                                clientes.show(cliente.id).url,
                                            )
                                        }
                                    >
                                        <TableCell>
                                            <div className="flex items-center gap-2.5">
                                                <ClienteAvatar
                                                    iniciales={
                                                        cliente.iniciales
                                                    }
                                                />
                                                <Link
                                                    href={clientes.show(
                                                        cliente.id,
                                                    )}
                                                    className="font-medium hover:underline"
                                                    onClick={(event) =>
                                                        event.stopPropagation()
                                                    }
                                                >
                                                    {cliente.nombre}
                                                </Link>
                                            </div>
                                        </TableCell>
                                        <TableCell className="tabular text-muted-foreground text-xs">
                                            {cliente.documento}
                                        </TableCell>
                                        <TableCell>
                                            {cliente.contacto ? (
                                                <>
                                                    <span className="block">
                                                        {
                                                            cliente.contacto
                                                                .nombre
                                                        }
                                                    </span>
                                                    <span className="text-text-tertiary block text-[11.5px]">
                                                        {cliente.contacto.email}
                                                    </span>
                                                </>
                                            ) : (
                                                <span className="text-text-tertiary">
                                                    —
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <EstadoBadge
                                                activo={cliente.is_active}
                                            />
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}

                    <footer className="border-surface-muted flex flex-wrap items-center justify-between gap-3 border-t px-4 py-2.5">
                        <span className="text-text-tertiary text-[12.5px]">
                            {paginationSummary(paginator)}
                        </span>

                        <div className="flex items-center gap-3">
                            <label className="text-text-tertiary flex items-center gap-1.5 text-[12.5px]">
                                Filas
                                <select
                                    value={filtros.filas}
                                    onChange={(event) =>
                                        apply({
                                            filas: Number(event.target.value),
                                        })
                                    }
                                    className="border-input rounded-md border bg-transparent px-1.5 py-0.5 text-[12.5px]"
                                >
                                    {opciones_filas.map((filas) => (
                                        <option key={filas} value={filas}>
                                            {filas}
                                        </option>
                                    ))}
                                </select>
                            </label>

                            {paginator.last_page > 1 && (
                                <Pagination className="w-auto">
                                    <PaginationContent>
                                        {paginator.links.map((link, index) => {
                                            if (index === 0) {
                                                return (
                                                    <PaginationItem key="prev">
                                                        <PaginationPrevious
                                                            href={link.url}
                                                        />
                                                    </PaginationItem>
                                                );
                                            }

                                            if (
                                                index ===
                                                paginator.links.length - 1
                                            ) {
                                                return (
                                                    <PaginationItem key="next">
                                                        <PaginationNext
                                                            href={link.url}
                                                        />
                                                    </PaginationItem>
                                                );
                                            }

                                            if (link.label.includes('...')) {
                                                return (
                                                    <PaginationItem
                                                        key={`gap-${index}`}
                                                    >
                                                        <PaginationEllipsis />
                                                    </PaginationItem>
                                                );
                                            }

                                            return (
                                                <PaginationItem
                                                    key={link.label}
                                                >
                                                    <PaginationLink
                                                        href={link.url}
                                                        isActive={link.active}
                                                    >
                                                        {link.label}
                                                    </PaginationLink>
                                                </PaginationItem>
                                            );
                                        })}
                                    </PaginationContent>
                                </Pagination>
                            )}
                        </div>
                    </footer>
                </section>
            </div>
        </>
    );
}
