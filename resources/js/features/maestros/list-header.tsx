import { Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { CatalogList } from '@/features/maestros/types';

function MetaRow({ label, items }: { label: string; items: string[] }) {
    if (items.length === 0) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center gap-2">
            <span className="text-text-tertiary text-[11px] font-semibold tracking-[0.04em] uppercase">
                {label}
            </span>
            {items.map((item) => (
                <span
                    key={item}
                    className="bg-surface-muted text-text-secondary rounded-full px-2 py-0.5 text-[11.5px]"
                >
                    {item}
                </span>
            ))}
        </div>
    );
}

export function ListHeader({
    list,
    onAdd,
}: {
    list: CatalogList;
    onAdd: () => void;
}) {
    return (
        <header className="bg-card rounded-card border-border shadow-card border p-5">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p className="text-brand-text text-[11px] font-semibold tracking-[0.04em] uppercase">
                        {list.group}
                    </p>
                    <h2 className="mt-1 text-[19px] font-bold tracking-[-0.02em]">
                        {list.title}
                    </h2>
                    {list.description && (
                        <p className="text-muted-foreground mt-1 max-w-prose text-[13px] leading-relaxed">
                            {list.description}
                        </p>
                    )}
                </div>

                {list.allows_user_values && (
                    <Button type="button" onClick={onAdd} className="shrink-0">
                        <Plus className="size-3.5" />
                        Agregar valor
                    </Button>
                )}
            </div>

            <div className="border-surface-muted mt-4 space-y-2 border-t pt-4">
                <MetaRow label="Usado en" items={list.used_in} />
                <MetaRow
                    label="Campos de cada valor"
                    items={list.field_labels}
                />
            </div>
        </header>
    );
}
