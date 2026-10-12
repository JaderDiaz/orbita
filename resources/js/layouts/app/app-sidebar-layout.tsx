import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import type { AppLayoutProps } from '@/types';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar" className="min-w-0 overflow-x-clip">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {/*
                 * The page's own container. The header stays outside it because
                 * it spans the full width and sticks to the top; only the page
                 * body carries the 28/32/56 padding.
                 */}
                <div className="min-w-0 flex-1 px-8 pt-7 pb-14">{children}</div>
            </AppContent>
        </AppShell>
    );
}
