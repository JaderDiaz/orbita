import { Badge } from '@/components/ui/badge';
import { CatalogIcon } from '@/features/maestros/catalog-icon';
import type { CatalogBadge as Pair } from '@/features/maestros/types';

/**
 * The colours arrive resolved from the server's CatalogColor enum, so this
 * component holds no copy of the palette.
 */
export function CatalogBadge({
    badge,
    icon,
}: {
    badge: Pair;
    icon?: string | null;
}) {
    return (
        <Badge
            variant="catalog"
            style={{ background: badge.background, color: badge.foreground }}
        >
            <CatalogIcon icon={icon ?? null} className="size-2.5" />
            {badge.text}
        </Badge>
    );
}

export function CodeChip({ code }: { code: string }) {
    return (
        <span className="rounded bg-[#1d1d1f] px-1.5 py-px font-mono text-[10.5px] font-medium tracking-wide text-white">
            {code}
        </span>
    );
}
