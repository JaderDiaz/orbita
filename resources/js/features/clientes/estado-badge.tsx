import { Badge } from '@/components/ui/badge';

/**
 * Activo and Inactivo are the two badge pairs the prototype uses for a client.
 * The colours come from the same eight-pair palette as the catalog's badges.
 */
export function EstadoBadge({ activo }: { activo: boolean }) {
    return (
        <Badge
            variant="catalog"
            style={
                activo
                    ? { background: '#e8f7ee', color: '#1a7f37' }
                    : { background: '#f0f0f2', color: '#6e6e73' }
            }
        >
            {activo ? 'Activo' : 'Inactivo'}
        </Badge>
    );
}
