import { Search, X } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { groupLists, type CatalogList } from '@/features/maestros/types';
import { cn } from '@/lib/utils';

/**
 * The catalog's index. Presentational: it reports selections and filter changes
 * and holds no state of its own.
 */
export function ListRail({
    lists,
    selectedKey,
    query,
    onQueryChange,
    onSelect,
}: {
    lists: CatalogList[];
    selectedKey: string | null;
    query: string;
    onQueryChange: (value: string) => void;
    onSelect: (list: CatalogList) => void;
}) {
    const groups = groupLists(lists);

    return (
        <aside className="w-full shrink-0 lg:sticky lg:top-20 lg:w-[250px] lg:self-start">
            <div className="relative">
                <Search className="text-text-tertiary pointer-events-none absolute top-1/2 left-3 size-3.5 -translate-y-1/2" />
                <Input
                    value={query}
                    onChange={(event) => onQueryChange(event.target.value)}
                    placeholder="Buscar lista o valor"
                    aria-label="Buscar lista o valor"
                    className="rounded-full pr-9 pl-9"
                />
                {query !== '' && (
                    <button
                        type="button"
                        onClick={() => onQueryChange('')}
                        aria-label="Limpiar la búsqueda"
                        className="text-text-tertiary hover:text-foreground absolute top-1/2 right-3 -translate-y-1/2"
                    >
                        <X className="size-3.5" />
                    </button>
                )}
            </div>

            {groups.length === 0 ? (
                <p className="text-text-tertiary mt-6 px-2 text-[13px]">
                    Ninguna lista coincide.
                </p>
            ) : (
                <nav className="mt-5 space-y-5">
                    {groups.map((group) => (
                        <div key={group.label}>
                            <p className="text-text-tertiary px-2 text-[10px] font-semibold tracking-[0.1em] uppercase">
                                {group.label}
                            </p>
                            <ul className="mt-1.5 space-y-0.5">
                                {group.lists.map((list) => (
                                    <li key={list.key}>
                                        <button
                                            type="button"
                                            onClick={() => onSelect(list)}
                                            aria-current={
                                                list.key === selectedKey
                                                    ? 'true'
                                                    : undefined
                                            }
                                            className={cn(
                                                'flex w-full items-center justify-between gap-2 rounded-md px-2 py-1.5 text-left text-[13px] transition-colors',
                                                list.key === selectedKey
                                                    ? 'bg-brand-surface text-brand-text font-semibold'
                                                    : 'text-text-secondary hover:bg-surface-subtle',
                                            )}
                                        >
                                            <span className="truncate">
                                                {list.title}
                                            </span>
                                            <span className="tabular text-text-tertiary text-xs">
                                                {list.active_count}
                                            </span>
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </nav>
            )}
        </aside>
    );
}
