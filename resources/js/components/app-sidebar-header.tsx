import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { UserPill } from '@/components/user-pill';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

/**
 * The shell's top bar: 56px, sticky and translucent, with the trail on the left
 * and the account on the right. The collapse control is not here — the
 * prototype keeps it inside the sidebar, and on mobile the sheet needs its own
 * trigger, which is the only reason one appears below.
 */
export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    return (
        <header className="sticky top-0 z-20 flex h-header shrink-0 items-center justify-between gap-4 border-b border-sidebar-border bg-[rgba(245,245,247,.72)] px-7 backdrop-blur-[20px] backdrop-saturate-[1.8]">
            <div className="flex min-w-0 items-center gap-2">
                <SidebarTrigger className="-ml-2 size-7 md:hidden" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>

            <UserPill />
        </header>
    );
}
