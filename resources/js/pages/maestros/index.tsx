import { Head, setLayoutProps } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Input } from '@/components/ui/input';
import { ListHeader } from '@/features/maestros/list-header';
import { ListRail } from '@/features/maestros/list-rail';
import {
    activityLabel,
    type CatalogList,
    type CatalogValue,
    type IconEntry,
    type PaletteEntry,
} from '@/features/maestros/types';
import { ValueEditor } from '@/features/maestros/value-editor';
import { ValueTable } from '@/features/maestros/value-table';
import { dashboard } from '@/routes';
import maestros from '@/routes/maestros';

type Props = {
    lists: CatalogList[];
    palette: PaletteEntry[];
    icons: IconEntry[];
    summary: string;
};

type Editing = { value: CatalogValue | null } | null;

function matches(haystack: string, needle: string): boolean {
    return haystack.toLowerCase().includes(needle.trim().toLowerCase());
}

/**
 * Maestros y Listas.
 *
 * The whole catalog arrives in one payload — a bounded administrative set whose
 * largest list holds seven values — so the two searches and the show-inactive
 * toggle filter it here instead of costing a round trip per keystroke.
 */
export default function Maestros({ lists, palette, icons, summary }: Props) {
    const [listQuery, setListQuery] = useState('');
    const [valueQuery, setValueQuery] = useState('');
    const [showInactive, setShowInactive] = useState(false);
    const [selectedKey, setSelectedKey] = useState<string | null>(
        lists[0]?.key ?? null,
    );
    const [editing, setEditing] = useState<Editing>(null);
    const [pendingDeleteId, setPendingDeleteId] = useState<number | null>(null);

    setLayoutProps({
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Maestros y listas', href: maestros.index() },
        ],
    });

    const visibleLists = useMemo(() => {
        if (listQuery.trim() === '') {
            return lists;
        }

        return lists.filter(
            (list) =>
                matches(list.title, listQuery) ||
                list.values.some((value) => matches(value.name, listQuery)),
        );
    }, [lists, listQuery]);

    const selected =
        lists.find((list) => list.key === selectedKey) ?? visibleLists[0] ?? null;

    const visibleValues = useMemo(() => {
        if (!selected) {
            return [];
        }

        return selected.values
            .filter((value) => showInactive || value.is_active)
            .filter((value) => {
                if (valueQuery.trim() === '') {
                    return true;
                }

                return (
                    matches(value.name, valueQuery) ||
                    matches(value.code ?? '', valueQuery) ||
                    matches(value.description ?? '', valueQuery)
                );
            })
            .sort((a, b) => a.position - b.position);
    }, [selected, showInactive, valueQuery]);

    // The prototype's arrows swapped rows inside the full array while the user
    // looked at a filtered view, so with a filter active the row traded places
    // with something invisible and appeared frozen. Reordering is offered only
    // when what is on screen is the whole list, in its real order — which is
    // the normal case, since hiding inactive values hides nothing on a list
    // that has none.
    const canReorder =
        valueQuery.trim() === '' &&
        (showInactive || (selected?.inactive_count ?? 0) === 0);

    const select = (list: CatalogList): void => {
        setSelectedKey(list.key);
        setEditing(null);
        setPendingDeleteId(null);
        setValueQuery('');
    };

    return (
        <>
            <Head title="Maestros y listas" />

            <div className="space-y-6">
                <header>
                    <h1 className="text-[28px] font-bold tracking-[-0.03em]">
                        Maestros y listas
                    </h1>
                    <p className="text-muted-foreground mt-1 max-w-prose text-[13px]">
                        {summary}
                    </p>
                </header>

                <div className="flex flex-col gap-6 lg:flex-row">
                    <ListRail
                        lists={visibleLists}
                        selectedKey={selected?.key ?? null}
                        query={listQuery}
                        onQueryChange={setListQuery}
                        onSelect={select}
                    />

                    {selected ? (
                        <div className="min-w-0 flex-1 space-y-4">
                            <ListHeader
                                list={selected}
                                onAdd={() => {
                                    setEditing({ value: null });
                                    setPendingDeleteId(null);
                                }}
                            />

                            {editing && (
                                <ValueEditor
                                    key={editing.value?.id ?? 'new'}
                                    list={selected}
                                    value={editing.value}
                                    palette={palette}
                                    icons={icons}
                                    onCancel={() => setEditing(null)}
                                />
                            )}

                            <section className="bg-card rounded-card border-border shadow-card border">
                                <div className="border-surface-muted flex flex-wrap items-center justify-between gap-3 border-b px-4 py-3">
                                    <div className="flex flex-wrap items-center gap-3">
                                        <span className="text-text-secondary text-[12.5px] font-medium">
                                            {activityLabel(selected)}
                                        </span>
                                        {selected.inactive_count > 0 && (
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setShowInactive(
                                                        !showInactive,
                                                    )
                                                }
                                                className="text-brand-text text-[12.5px] hover:underline"
                                            >
                                                {showInactive
                                                    ? 'Ocultar inactivos'
                                                    : 'Mostrar inactivos'}
                                            </button>
                                        )}
                                    </div>
                                    <Input
                                        value={valueQuery}
                                        onChange={(event) =>
                                            setValueQuery(event.target.value)
                                        }
                                        placeholder="Buscar en la lista"
                                        aria-label="Buscar en la lista"
                                        className="w-full sm:w-[220px]"
                                    />
                                </div>

                                <ValueTable
                                    list={selected}
                                    values={visibleValues}
                                    canReorder={canReorder}
                                    editingId={editing?.value?.id ?? null}
                                    pendingDeleteId={pendingDeleteId}
                                    onEdit={(value) => {
                                        setEditing({ value });
                                        setPendingDeleteId(null);
                                    }}
                                    onRequestDelete={(value) =>
                                        setPendingDeleteId(value.id)
                                    }
                                    onCancelDelete={() =>
                                        setPendingDeleteId(null)
                                    }
                                />

                                {selected.allows_user_values && (
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setEditing({ value: null });
                                            setPendingDeleteId(null);
                                        }}
                                        className="text-text-tertiary hover:bg-surface-subtle hover:text-brand-text border-surface-muted flex w-full items-center gap-1.5 border-t px-4 py-2.5 text-[12.5px] transition-colors"
                                    >
                                        <Plus className="size-3.5" />
                                        Agregar valor
                                    </button>
                                )}
                            </section>
                        </div>
                    ) : (
                        <p className="text-text-tertiary flex-1 text-[13px]">
                            Ninguna lista coincide.
                        </p>
                    )}
                </div>
            </div>
        </>
    );
}
