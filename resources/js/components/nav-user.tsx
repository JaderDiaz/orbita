import { Link, router, usePage } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import { UserAvatar } from '@/components/user-avatar';
import { useSidebar } from '@/components/ui/sidebar';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { Auth } from '@/types/auth';

/**
 * The sidebar's footer account row. The prototype splits it in two: the row
 * itself goes to the profile, and signing out is its own button beside it — not
 * an item hidden inside a menu. The header pill carries the full menu.
 */
export function NavUser() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const { state, isMobile } = useSidebar();
    const cleanup = useMobileNavigation();

    if (!auth.user) {
        return null;
    }

    const collapsed = state === 'collapsed' && !isMobile;

    const handleLogout = () => {
        cleanup();
        router.flushAll();
    };

    return (
        <div className="flex items-center gap-1">
            <Link
                href={edit()}
                prefetch
                onClick={cleanup}
                title="Mi perfil"
                className={`flex min-w-0 flex-1 items-center gap-[9px] rounded-[10px] py-1.5 transition-colors hover:bg-black/[.04] ${
                    collapsed ? 'justify-center px-0' : 'px-2'
                }`}
            >
                <UserAvatar
                    user={auth.user}
                    className="size-[30px] text-[11px]"
                />
                {!collapsed && (
                    <div className="grid min-w-0 text-left leading-[1.25]">
                        <span className="truncate text-[12.5px] font-semibold">
                            {auth.user.name}
                        </span>
                        {auth.roleLabel && (
                            <span className="truncate text-[11px] text-text-tertiary">
                                {auth.roleLabel}
                            </span>
                        )}
                    </div>
                )}
            </Link>

            {!collapsed && (
                <Link
                    href={logout()}
                    as="button"
                    onClick={handleLogout}
                    title="Cerrar sesión"
                    data-test="logout-button"
                    className="flex size-8 shrink-0 items-center justify-center rounded-[9px] text-text-tertiary transition-colors hover:bg-[#ffeceb] hover:text-destructive"
                >
                    <LogOut className="size-[15px]" />
                    <span className="sr-only">Cerrar sesión</span>
                </Link>
            )}
        </div>
    );
}
