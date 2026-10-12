import { Link } from '@inertiajs/react';
import { Fragment } from 'react';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

/**
 * The header's trail. Written as plain markup rather than shadcn's `Breadcrumb`
 * because the prototype separates with a slash in `#d1d1d6`, not a chevron, and
 * sets its own sizes.
 */
export function Breadcrumbs({
    breadcrumbs,
}: {
    breadcrumbs: BreadcrumbItemType[];
}) {
    if (breadcrumbs.length === 0) {
        return null;
    }

    return (
        <nav
            aria-label="Ruta de navegación"
            className="flex min-w-0 items-center gap-[9px] text-[13.5px]"
        >
            {breadcrumbs.map((item, index) => {
                const isLast = index === breadcrumbs.length - 1;

                return (
                    <Fragment key={index}>
                        {isLast ? (
                            <span
                                aria-current="page"
                                className="truncate font-medium text-foreground"
                            >
                                {item.title}
                            </span>
                        ) : (
                            <Link
                                href={item.href}
                                className="truncate text-text-tertiary transition-colors hover:text-foreground"
                            >
                                {item.title}
                            </Link>
                        )}

                        {!isLast && (
                            <span
                                aria-hidden
                                className="text-[#d1d1d6] select-none"
                            >
                                /
                            </span>
                        )}
                    </Fragment>
                );
            })}
        </nav>
    );
}
