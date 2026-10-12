import { router } from '@inertiajs/react';
import { ChevronDown, ChevronUp, Lock, Pencil, Star, Trash2 } from 'lucide-react';
import MaestrosController from '@/actions/App/Http/Controllers/MaestrosController';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { CatalogBadge, CodeChip } from '@/features/maestros/catalog-badge';
import {
    usageLabel,
    type CatalogList,
    type CatalogValue,
} from '@/features/maestros/types';
import { cn } from '@/lib/utils';

function visit(
    url: string,
    method: 'post' | 'delete',
    data: Record<string, string> = {},
): void {
    router.visit(url, {
        method,
        data,
        preserveScroll: true,
        preserveState: false,
    });
}

function IconButton({
    title,
    onClick,
    disabled,
    children,
    className,
}: {
    title: string;
    onClick?: () => void;
    disabled?: boolean;
    children: React.ReactNode;
    className?: string;
}) {
    return (
        <button
            type="button"
            title={title}
            aria-label={title}
            onClick={onClick}
            disabled={disabled}
            className={cn(
                'text-text-tertiary hover:bg-surface-muted hover:text-foreground flex size-[26px] items-center justify-center rounded-md transition-colors disabled:pointer-events-none disabled:opacity-25',
                className,
            )}
        >
            {children}
        </button>
    );
}

/**
 * The confirmation lives inside the row, as the prototype does it, so the value
 * being removed stays in view. When records reference it the delete button is
 * not rendered at all: deactivating is the only offer.
 */
function DeleteStrip({
    value,
    onCancel,
}: {
    value: CatalogValue;
    onCancel: () => void;
}) {
    const inUse = value.usage > 0;

    return (
        <div className="rounded-md border border-[#ffd6d6] bg-[#fff5f5] px-3 py-2 text-[12.5px] text-[#b4121b]">
            <p>
                {inUse
                    ? `«${value.name}» está en uso en ${usageLabel(value.usage)}. Desactívalo para que no aparezca en registros nuevos.`
                    : `¿Eliminar «${value.name}»? Esta acción no se puede deshacer.`}
            </p>
            <div className="mt-2 flex items-center gap-2">
                <Button type="button" variant="secondary" onClick={onCancel}>
                    Cancelar
                </Button>
                {inUse ? (
                    <Button
                        type="button"
                        className="bg-foreground hover:bg-foreground/90"
                        onClick={() =>
                            visit(
                                MaestrosController.toggle.url({
                                    value: value.id,
                                }),
                                'post',
                            )
                        }
                    >
                        Desactivar
                    </Button>
                ) : (
                    <Button
                        type="button"
                        variant="destructive"
                        onClick={() =>
                            visit(
                                MaestrosController.destroy.url({
                                    value: value.id,
                                }),
                                'delete',
                            )
                        }
                    >
                        Eliminar
                    </Button>
                )}
            </div>
        </div>
    );
}

export function ValueTable({
    list,
    values,
    canReorder,
    editingId,
    pendingDeleteId,
    onEdit,
    onRequestDelete,
    onCancelDelete,
}: {
    list: CatalogList;
    values: CatalogValue[];
    canReorder: boolean;
    editingId: number | null;
    pendingDeleteId: number | null;
    onEdit: (value: CatalogValue) => void;
    onRequestDelete: (value: CatalogValue) => void;
    onCancelDelete: () => void;
}) {
    if (values.length === 0) {
        return (
            <p className="text-text-tertiary px-3 py-6 text-[13px]">
                No hay valores que coincidan.
            </p>
        );
    }

    const ordered = [...list.values].sort((a, b) => a.position - b.position);

    return (
        <Table>
            <TableHeader>
                <TableRow className="hover:bg-transparent">
                    <TableHead className="w-[30px]" />
                    <TableHead>Valor</TableHead>
                    <TableHead className="w-[170px]">Vista</TableHead>
                    <TableHead className="w-[70px]">Activo</TableHead>
                    <TableHead className="w-[100px]" />
                </TableRow>
            </TableHeader>
            <TableBody>
                {values.map((value) => {
                    const index = ordered.findIndex(
                        (candidate) => candidate.id === value.id,
                    );
                    const reorderTitle = canReorder
                        ? undefined
                        : 'Quita los filtros para reordenar';

                    return (
                        <TableRow
                            key={value.id}
                            className={cn(
                                editingId === value.id && 'bg-brand-surface',
                                !value.is_active && 'opacity-45',
                            )}
                        >
                            <TableCell className="align-top">
                                <div className="flex flex-col">
                                    <IconButton
                                        title={reorderTitle ?? 'Subir'}
                                        disabled={!canReorder || index <= 0}
                                        onClick={() =>
                                            visit(
                                                MaestrosController.move.url({
                                                    value: value.id,
                                                }),
                                                'post',
                                                { direction: 'up' },
                                            )
                                        }
                                        className="size-4"
                                    >
                                        <ChevronUp className="size-3.5" />
                                    </IconButton>
                                    <IconButton
                                        title={reorderTitle ?? 'Bajar'}
                                        disabled={
                                            !canReorder ||
                                            index === ordered.length - 1
                                        }
                                        onClick={() =>
                                            visit(
                                                MaestrosController.move.url({
                                                    value: value.id,
                                                }),
                                                'post',
                                                { direction: 'down' },
                                            )
                                        }
                                        className="size-4"
                                    >
                                        <ChevronDown className="size-3.5" />
                                    </IconButton>
                                </div>
                            </TableCell>

                            <TableCell className="align-top">
                                <div className="flex items-center gap-2">
                                    <span className="font-medium">
                                        {value.name}
                                    </span>
                                    {value.is_system && (
                                        <span
                                            title="Lo usa el código: se puede renombrar, no eliminar ni desactivar"
                                            aria-label="Lo usa el código"
                                            className="text-text-tertiary inline-flex"
                                        >
                                            <Lock className="size-3" />
                                        </span>
                                    )}
                                </div>
                                <div className="text-text-tertiary mt-0.5 flex flex-wrap items-center gap-2 text-[11.5px]">
                                    <span>{usageLabel(value.usage)}</span>
                                    {value.is_default && (
                                        <span className="bg-brand-surface text-brand-text rounded-full px-1.5 py-px text-[10px] font-semibold">
                                            Predeterminado
                                        </span>
                                    )}
                                </div>
                                {list.fields.includes('desc') &&
                                    value.description && (
                                        <p className="text-muted-foreground mt-0.5 text-[12px]">
                                            {value.description}
                                        </p>
                                    )}
                                {list.flag && value.flag && (
                                    <p className="mt-0.5 text-[11.5px] text-[#1a7f37]">
                                        ✓ {list.flag.label}
                                    </p>
                                )}

                                {pendingDeleteId === value.id && (
                                    <div className="mt-2">
                                        <DeleteStrip
                                            value={value}
                                            onCancel={onCancelDelete}
                                        />
                                    </div>
                                )}
                            </TableCell>

                            <TableCell className="align-top">
                                <div className="flex flex-wrap items-center gap-1.5">
                                    <CatalogBadge
                                        badge={value.badge}
                                        icon={value.icon}
                                    />
                                    {list.fields.includes('code') &&
                                        value.code &&
                                        value.badge.text !== value.code && (
                                            <CodeChip code={value.code} />
                                        )}
                                </div>
                            </TableCell>

                            <TableCell className="align-top">
                                <button
                                    type="button"
                                    role="switch"
                                    aria-checked={value.is_active}
                                    title={
                                        value.is_system
                                            ? 'Lo usa el código: no se puede desactivar'
                                            : value.is_active
                                              ? 'Desactivar'
                                              : 'Reactivar'
                                    }
                                    disabled={value.is_system}
                                    onClick={() =>
                                        visit(
                                            MaestrosController.toggle.url({
                                                value: value.id,
                                            }),
                                            'post',
                                        )
                                    }
                                    className={cn(
                                        'relative h-5 w-[34px] rounded-full transition-colors disabled:opacity-40',
                                        value.is_active
                                            ? 'bg-[#34c759]'
                                            : 'bg-[#e5e5ea]',
                                    )}
                                >
                                    <span
                                        className={cn(
                                            'absolute top-0.5 size-4 rounded-full bg-white shadow transition-all',
                                            value.is_active
                                                ? 'left-[16px]'
                                                : 'left-0.5',
                                        )}
                                    />
                                </button>
                            </TableCell>

                            <TableCell className="align-top">
                                <div className="flex items-center gap-0.5">
                                    <IconButton
                                        title="Marcar como predeterminado"
                                        disabled={
                                            value.is_default ||
                                            !value.is_active
                                        }
                                        onClick={() =>
                                            visit(
                                                MaestrosController.setDefault.url(
                                                    { value: value.id },
                                                ),
                                                'post',
                                            )
                                        }
                                    >
                                        <Star
                                            className={cn(
                                                'size-3.5',
                                                value.is_default &&
                                                    'fill-brand text-brand',
                                            )}
                                        />
                                    </IconButton>
                                    <IconButton
                                        title="Editar"
                                        onClick={() => onEdit(value)}
                                    >
                                        <Pencil className="size-3.5" />
                                    </IconButton>
                                    <IconButton
                                        title={
                                            value.is_system
                                                ? 'Lo usa el código: no se puede eliminar'
                                                : 'Eliminar'
                                        }
                                        disabled={value.is_system}
                                        onClick={() => onRequestDelete(value)}
                                        className="hover:text-destructive"
                                    >
                                        <Trash2 className="size-3.5" />
                                    </IconButton>
                                </div>
                            </TableCell>
                        </TableRow>
                    );
                })}
            </TableBody>
        </Table>
    );
}
