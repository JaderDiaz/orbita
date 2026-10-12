import { cn } from '@/lib/utils';

/**
 * The rounded square with two initials the prototype uses in the list, and the
 * orange gradient version it uses on the detail cover.
 */
export function ClienteAvatar({
    iniciales,
    size = 'sm',
}: {
    iniciales: string;
    size?: 'sm' | 'lg';
}) {
    return (
        <span
            className={cn(
                'flex shrink-0 items-center justify-center font-semibold',
                size === 'sm'
                    ? 'bg-surface-muted text-text-secondary size-7 rounded-md text-[11px]'
                    : 'rounded-panel size-[72px] bg-linear-to-b from-[#fb923c] to-[#f97316] text-[26px] text-white',
            )}
        >
            {iniciales}
        </span>
    );
}
