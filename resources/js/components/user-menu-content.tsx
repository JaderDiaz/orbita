import { Link, router } from '@inertiajs/react';
import { KeyRound, LogOut, UserRound } from 'lucide-react';
import {
    DropdownMenuItem,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { UserAvatar } from '@/components/user-avatar';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import { edit as securityEdit } from '@/routes/security';
import type { User } from '@/types';

type Props = {
    user: User;
    roleLabel?: string | null;
};

const ITEM =
    'flex w-full cursor-pointer items-center gap-2.5 rounded-[9px] px-2.5 py-2 text-[13px]';

/**
 * The account menu. One list for both triggers — the header pill and the
 * sidebar footer — so the two cannot drift apart.
 */
export function UserMenuContent({ user, roleLabel }: Props) {
    const cleanup = useMobileNavigation();

    const handleLogout = () => {
        cleanup();
        router.flushAll();
    };

    return (
        <>
            <div className="flex items-center gap-[11px] px-2.5 pt-2.5 pb-3">
                <UserAvatar user={user} className="size-[38px] text-[13px]" />
                <div className="grid min-w-0 text-left leading-[1.3]">
                    <span className="truncate text-[13.5px] font-semibold">
                        {user.name}
                    </span>
                    <span className="truncate text-[12px] text-muted-foreground">
                        {user.email}
                    </span>
                    {roleLabel && (
                        <span className="mt-[5px] justify-self-start rounded-pill bg-brand-surface px-2 py-0.5 text-[10.5px] font-semibold text-brand-text">
                            {roleLabel}
                        </span>
                    )}
                </div>
            </div>

            <DropdownMenuSeparator />

            <DropdownMenuItem asChild>
                <Link className={ITEM} href={edit()} prefetch onClick={cleanup}>
                    <UserRound className="size-[15px] text-muted-foreground" />
                    Mi perfil
                </Link>
            </DropdownMenuItem>

            <DropdownMenuItem asChild>
                <Link className={ITEM} href={securityEdit()} onClick={cleanup}>
                    <KeyRound className="size-[15px] text-muted-foreground" />
                    Contraseña y seguridad
                </Link>
            </DropdownMenuItem>

            <DropdownMenuSeparator />

            {/*
             * Not shadcn's `variant="destructive"`: that paints the label
             * `--destructive-foreground`, which is white here and would be
             * invisible on the menu's white surface.
             */}
            <DropdownMenuItem asChild>
                <Link
                    className={`${ITEM} text-destructive focus:bg-[#ffeceb] focus:text-destructive [&_svg]:text-destructive`}
                    href={logout()}
                    as="button"
                    onClick={handleLogout}
                    data-test="logout-button"
                >
                    <LogOut className="size-[15px]" />
                    Cerrar sesión
                </Link>
            </DropdownMenuItem>
        </>
    );
}
