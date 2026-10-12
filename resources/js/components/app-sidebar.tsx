import { Link } from '@inertiajs/react';
import { PanelLeftClose, PanelLeftOpen } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    useSidebar,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';

/**
 * The prototype keeps the collapse control inside the sidebar, not in the
 * header: next to the brand while open, alone above the user row once
 * collapsed. Written here rather than reusing `SidebarTrigger` because that one
 * is a ghost button with its own sizing and an English label.
 */
function CollapseToggle({ collapsed }: { collapsed: boolean }) {
    const { toggleSidebar } = useSidebar();
    const Icon = collapsed ? PanelLeftOpen : PanelLeftClose;

    return (
        <button
            type="button"
            onClick={toggleSidebar}
            title={collapsed ? 'Expandir menú' : 'Colapsar menú'}
            className={
                collapsed
                    ? 'flex h-8 items-center justify-center rounded-md text-text-tertiary transition-colors hover:bg-black/5 hover:text-foreground'
                    : 'flex size-7 shrink-0 items-center justify-center rounded-lg text-text-tertiary transition-colors hover:bg-black/5 hover:text-foreground'
            }
        >
            <Icon className="size-4" />
            <span className="sr-only">
                {collapsed ? 'Expandir menú' : 'Colapsar menú'}
            </span>
        </button>
    );
}

export function AppSidebar() {
    const { state } = useSidebar();
    const collapsed = state === 'collapsed';

    return (
        <Sidebar
            collapsible="icon"
            variant="sidebar"
            className="group-data-[side=left]:border-sidebar-border"
        >
            <SidebarHeader className="min-h-[62px] flex-row items-center gap-2.5 pt-[18px] pr-3 pb-3.5 pl-[18px] group-data-[collapsible=icon]:pr-[18px]">
                <Link
                    href={dashboard()}
                    prefetch
                    className="flex min-w-0 flex-1 items-center gap-2.5"
                >
                    <AppLogo />
                </Link>

                {!collapsed && <CollapseToggle collapsed={false} />}
            </SidebarHeader>

            <SidebarContent className="gap-3 px-3 pt-1 pb-3 group-data-[collapsible=icon]:px-2.5">
                <NavMain />
            </SidebarContent>

            <SidebarFooter className="gap-1.5 border-t border-border px-3 pt-2.5 pb-3.5 group-data-[collapsible=icon]:px-2.5">
                {collapsed && <CollapseToggle collapsed />}
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
