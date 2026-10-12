import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
};

/**
 * A navigation item as the server resolves it. `href` is null and `enabled` is
 * false for a section whose module is not built yet: it stays visible, because
 * the navigation is the map of the product, but it does not navigate.
 */
export type SharedNavItem = {
    key: string;
    title: string;
    href: string | null;
    enabled: boolean;
    children?: SharedNavItem[];
};

export type SharedNavGroup = {
    label: string | null;
    items: SharedNavItem[];
};
