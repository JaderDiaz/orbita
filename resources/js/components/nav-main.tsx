import { Link, usePage } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { useState } from 'react';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { sectionPresentation, tileGradient } from '@/config/navigation';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { SharedNavGroup, SharedNavItem } from '@/types/navigation';

function SectionTile({ sectionKey }: { sectionKey: string }) {
    const { icon: Icon, hue } = sectionPresentation(sectionKey);

    return (
        <span
            className="flex size-6 shrink-0 items-center justify-center rounded-[7px] text-white shadow-[inset_0_1px_0_rgba(255,255,255,.25),0_1px_2px_rgba(0,0,0,.12)]"
            style={{ backgroundImage: tileGradient(hue) }}
        >
            <Icon className="size-3.5" />
        </span>
    );
}

/**
 * A section whose module is not built yet. It stays visible because the
 * navigation is the map of the product, but it does not navigate.
 */
function DisabledItem({ item }: { item: SharedNavItem }) {
    return (
        <SidebarMenuItem>
            <SidebarMenuButton
                disabled
                tooltip={{ children: `${item.title} · aún no disponible` }}
                className="cursor-not-allowed opacity-45"
            >
                <SectionTile sectionKey={item.key} />
                <span>{item.title}</span>
            </SidebarMenuButton>
        </SidebarMenuItem>
    );
}

function LinkItem({ item }: { item: SharedNavItem }) {
    const { isCurrentUrl } = useCurrentUrl();

    return (
        <SidebarMenuItem>
            <SidebarMenuButton
                asChild
                isActive={isCurrentUrl(item.href ?? '')}
                tooltip={{ children: item.title }}
            >
                <Link href={item.href ?? ''} prefetch>
                    <SectionTile sectionKey={item.key} />
                    <span>{item.title}</span>
                </Link>
            </SidebarMenuButton>
        </SidebarMenuItem>
    );
}

function NestedItem({ item }: { item: SharedNavItem }) {
    const [open, setOpen] = useState(false);
    const children = item.children ?? [];

    return (
        <Collapsible open={open} onOpenChange={setOpen} asChild>
            <SidebarMenuItem>
                <CollapsibleTrigger asChild>
                    <SidebarMenuButton tooltip={{ children: item.title }}>
                        <SectionTile sectionKey={item.key} />
                        <span>{item.title}</span>
                        <ChevronRight className="ml-auto size-3.5 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                    </SidebarMenuButton>
                </CollapsibleTrigger>

                <CollapsibleContent>
                    <SidebarMenuSub>
                        {children.map((child) =>
                            child.enabled && child.href ? (
                                <SidebarMenuSubItem key={child.key}>
                                    <SidebarMenuSubButton asChild>
                                        <Link href={child.href} prefetch>
                                            <span>{child.title}</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            ) : (
                                <SidebarMenuSubItem key={child.key}>
                                    <SidebarMenuSubButton
                                        className="cursor-not-allowed opacity-45"
                                        aria-disabled
                                    >
                                        <span>{child.title}</span>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            ),
                        )}
                    </SidebarMenuSub>
                </CollapsibleContent>
            </SidebarMenuItem>
        </Collapsible>
    );
}

/**
 * Renders the navigation the server already filtered by role. This component
 * makes no authorization decision.
 */
export function NavMain() {
    const { navigation } = usePage<{ navigation: SharedNavGroup[] }>().props;

    return (
        <>
            {navigation.map((group, index) => (
                <SidebarGroup
                    key={group.label ?? `group-${index}`}
                    className="px-2 py-0"
                >
                    {group.label && (
                        <SidebarGroupLabel className="text-[10px] font-semibold tracking-[0.1em] text-text-tertiary uppercase">
                            {group.label}
                        </SidebarGroupLabel>
                    )}

                    <SidebarMenu>
                        {group.items.map((item) => {
                            if (item.children?.length) {
                                return <NestedItem key={item.key} item={item} />;
                            }

                            return item.enabled && item.href ? (
                                <LinkItem key={item.key} item={item} />
                            ) : (
                                <DisabledItem key={item.key} item={item} />
                            );
                        })}
                    </SidebarMenu>
                </SidebarGroup>
            ))}
        </>
    );
}
