import AppLogoIcon from '@/components/app-logo-icon';

/**
 * The sidebar's brand block. The mark is 30px here and 48px on the login, so
 * this passes its own shadow: the admin shell uses a tight drop, not the login's
 * wide glow.
 */
export default function AppLogo() {
    return (
        <>
            <AppLogoIcon
                className="flex aspect-square size-[30px] shrink-0 items-center justify-center rounded-[9px] text-[14px] font-bold text-white"
                style={{
                    boxShadow:
                        '0 2px 6px rgba(249,115,22,.35), inset 0 1px 0 rgba(255,255,255,.35)',
                }}
            />
            <div className="grid min-w-0 flex-1 text-left leading-[1.2] group-data-[collapsible=icon]:hidden">
                <span className="truncate text-[13.5px] font-semibold tracking-[-0.01em] text-foreground">
                    Orbita
                </span>
                <span className="truncate text-[11px] text-muted-foreground">
                    Agencia
                </span>
            </div>
        </>
    );
}
