import { Head, setLayoutProps, usePage } from '@inertiajs/react';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { dashboard } from '@/routes';
import type { Auth } from '@/types/auth';

export default function Dashboard() {
    const { auth } = usePage<{ auth: Auth }>().props;

    // The prototype titles this screen differently per role: an administrador
    // lands on "Panel general", a miembro on "Mi panel".
    const isAdministrative = auth.role === 'owner' || auth.role === 'admin';
    const title = isAdministrative ? 'Panel general' : 'Mi panel';

    setLayoutProps({
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title, href: dashboard() },
        ],
    });

    return (
        <>
            <Head title={title} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20" />
                    </div>
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20" />
                    </div>
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20" />
                    </div>
                </div>
                <div className="relative min-h-[100vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 md:min-h-min">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20" />
                </div>
            </div>
        </>
    );
}
