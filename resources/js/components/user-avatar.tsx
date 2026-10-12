import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import type { User } from '@/types';

type Props = {
    user: User;
    className?: string;
};

/**
 * The account avatar, shared by the three places the prototype shows it: the
 * header pill, the sidebar footer and the account menu's heading. Only the size
 * changes between them, so it comes in through `className`; the indigo gradient
 * and the inner highlight are the identity and stay fixed.
 */
export function UserAvatar({ user, className }: Props) {
    const getInitials = useInitials();

    return (
        <Avatar
            className={cn('shrink-0 overflow-hidden rounded-full', className)}
        >
            <AvatarImage src={user.avatar} alt={user.name} />
            <AvatarFallback
                className="rounded-full font-semibold text-white"
                style={{
                    backgroundImage:
                        'linear-gradient(135deg, #a78bfa, #6366f1)',
                    boxShadow: 'inset 0 1px 0 rgba(255,255,255,.25)',
                }}
            >
                {getInitials(user.name)}
            </AvatarFallback>
        </Avatar>
    );
}
