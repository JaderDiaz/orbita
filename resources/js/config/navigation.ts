import {
    Banknote,
    Clock,
    FileText,
    LayoutGrid,
    Receipt,
    Settings,
    SlidersHorizontal,
    TrendingDown,
    Users,
    UsersRound,
    type LucideIcon,
} from 'lucide-react';

/**
 * Presentation for each navigation section.
 *
 * What a role may SEE is decided on the server (see
 * app/Support/Navigation/SidebarNavigation.php) and arrives already filtered in
 * the `navigation` shared prop. This file only says how a section looks, keyed
 * by the server's section key.
 *
 * The hues come from the prototype's `TILE` map: the sidebar icon tiles carry a
 * colour per section, so the orange accent does not dominate the shell.
 */
type SectionPresentation = {
    icon: LucideIcon;
    hue: string;
};

const SECTIONS: Record<string, SectionPresentation> = {
    panel: { icon: LayoutGrid, hue: '#5856d6' },
    'mi-panel': { icon: LayoutGrid, hue: '#5856d6' },
    clientes: { icon: Users, hue: '#007aff' },
    cotizaciones: { icon: FileText, hue: '#30b0c7' },
    proyectos: { icon: LayoutGrid, hue: '#f97316' },
    documentos: { icon: FileText, hue: '#32ade6' },
    facturas: { icon: Receipt, hue: '#34c759' },
    egresos: { icon: TrendingDown, hue: '#ff3b30' },
    equipo: { icon: UsersRound, hue: '#af52de' },
    'mi-tiempo': { icon: Clock, hue: '#ff9f0a' },
    configuracion: { icon: Settings, hue: '#8e8e93' },
    usuarios: { icon: UsersRound, hue: '#8e8e93' },
    parametros: { icon: SlidersHorizontal, hue: '#8e8e93' },
    maestros: { icon: Banknote, hue: '#8e8e93' },
};

const FALLBACK: SectionPresentation = { icon: LayoutGrid, hue: '#8e8e93' };

export function sectionPresentation(key: string): SectionPresentation {
    return SECTIONS[key] ?? FALLBACK;
}

/**
 * The gradient the prototype applies to a sidebar icon tile.
 */
export function tileGradient(hue: string): string {
    return `linear-gradient(180deg, ${hue}bb, ${hue})`;
}
