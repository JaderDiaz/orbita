import {
    ArrowDown,
    ArrowUp,
    Banknote,
    Box,
    Bug,
    Check,
    Clock,
    CreditCard,
    FileText,
    Flag,
    Landmark,
    Lock,
    Minus,
    PenLine,
    Sparkles,
    Tag,
    type LucideIcon,
} from 'lucide-react';

/**
 * Maps the icon keys the database constrains to their glyphs. The key set is
 * closed by the CatalogIcon enum, so a stored key always has an icon here.
 */
const ICONS: Record<string, LucideIcon> = {
    sparkle: Sparkles,
    bug: Bug,
    up: ArrowUp,
    down: ArrowDown,
    minus: Minus,
    flag: Flag,
    file: FileText,
    pen: PenLine,
    check: Check,
    lock: Lock,
    box: Box,
    card: CreditCard,
    bank: Landmark,
    cash: Banknote,
    tag: Tag,
    clock: Clock,
};

export function CatalogIcon({
    icon,
    className,
}: {
    icon: string | null;
    className?: string;
}) {
    if (!icon) {
        return null;
    }

    const Glyph = ICONS[icon];

    return Glyph ? <Glyph className={className} /> : null;
}
