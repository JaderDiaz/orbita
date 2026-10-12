import { usePage } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { UserMenuContent } from '@/components/user-menu-content';
import { UserAvatar } from '@/components/user-avatar';
import type { Auth } from '@/types/auth';

/**
 * The account pill on the right of the header, with the full account menu
 * behind it. The sidebar footer keeps its own shortcuts to the same two
 * destinations; both come from the prototype, which shows the account in both
 * places.
 */
export function UserPill() {
    const { auth } = usePage<{ auth: Auth }>().props;

    if (!auth.user) {
        return null;
    }

    const firstName = auth.user.name.trim().split(/\s+/)[0];

    return (
        <DropdownMenu>
            <DropdownMenuTrigger
                className="flex items-center gap-2 rounded-pill border border-transparent py-[3px] pr-2.5 pl-[3px] transition-colors hover:border-input hover:bg-card data-[state=open]:border-input data-[state=open]:bg-card"
                data-test="user-menu-trigger"
            >
                <UserAvatar user={auth.user} className="size-7 text-[11px]" />
                <span className="text-[12.5px] font-medium whitespace-nowrap">
                    {firstName}
                </span>
                <ChevronDown className="size-3 text-text-tertiary" />
                <span className="sr-only">Abrir menú de cuenta</span>
            </DropdownMenuTrigger>

            <DropdownMenuContent
                align="end"
                sideOffset={10}
                className="w-[270px] rounded-card border-border/80 p-1.5 shadow-[0_18px_50px_-12px_rgba(0,0,0,.28)] backdrop-blur-[20px]"
            >
                <UserMenuContent user={auth.user} roleLabel={auth.roleLabel} />
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
