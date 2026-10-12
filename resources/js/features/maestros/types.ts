export type CatalogBadge = {
    background: string;
    foreground: string;
    text: string;
};

export type CatalogValue = {
    id: number;
    name: string;
    code: string | null;
    color: string | null;
    icon: string | null;
    description: string | null;
    flag: boolean;
    is_default: boolean;
    is_active: boolean;
    is_system: boolean;
    position: number;
    usage: number;
    badge: CatalogBadge;
};

export type CatalogFlag = {
    key: string;
    label: string;
};

export type CatalogList = {
    id: number;
    key: string;
    group: string;
    title: string;
    description: string | null;
    fields: string[];
    used_in: string[];
    allows_user_values: boolean;
    flag: CatalogFlag | null;
    field_labels: string[];
    active_count: number;
    inactive_count: number;
    values: CatalogValue[];
};

export type PaletteEntry = {
    key: string;
    label: string;
    background: string;
    foreground: string;
};

export type IconEntry = {
    key: string;
    label: string;
};

export type CatalogGroup = {
    label: string;
    lists: CatalogList[];
};

/**
 * Groups keep the order the server sent, which is the order the lists carry.
 */
export function groupLists(lists: CatalogList[]): CatalogGroup[] {
    const groups: CatalogGroup[] = [];

    for (const list of lists) {
        const existing = groups.find((group) => group.label === list.group);

        if (existing) {
            existing.lists.push(list);
        } else {
            groups.push({ label: list.group, lists: [list] });
        }
    }

    return groups;
}

export function usageLabel(usage: number): string {
    if (usage === 0) {
        return 'Sin uso';
    }

    return usage === 1 ? '1 registro' : `${usage} registros`;
}

export function activityLabel(list: CatalogList): string {
    const active = `${list.active_count} ${list.active_count === 1 ? 'activo' : 'activos'}`;

    if (list.inactive_count === 0) {
        return active;
    }

    // The prototype rendered "1 inactivos" here.
    const inactive = `${list.inactive_count} ${list.inactive_count === 1 ? 'inactivo' : 'inactivos'}`;

    return `${active} · ${inactive}`;
}
