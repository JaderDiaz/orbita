import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <AppLogoIcon className="flex aspect-square size-8 items-center justify-center rounded-[9px] text-[13px] font-semibold text-white" />
            <div className="ml-1 grid flex-1 text-left">
                <span className="truncate text-[13.5px] leading-tight font-semibold text-foreground">
                    Orbita
                </span>
                <span className="truncate text-[11px] text-text-tertiary">
                    Agencia
                </span>
            </div>
        </>
    );
}
