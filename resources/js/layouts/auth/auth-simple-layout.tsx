import AppLogoIcon from '@/components/app-logo-icon';
import type { AuthLayoutProps } from '@/types';

/**
 * The authentication shell, taken from `Prototipo Login.dc.html`: the radial
 * glow, a 48px gradient mark, and the form on an 18px card.
 */
export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="flex min-h-svh flex-col items-center justify-center bg-background shell-glow px-5 py-10">
            <div className="flex w-full max-w-[380px] flex-col gap-7">
                <div className="flex flex-col items-center gap-3.5 text-center">
                    <AppLogoIcon className="flex size-12 items-center justify-center rounded-[13px] text-[17px] font-semibold text-white" />

                    <div className="flex flex-col gap-1.5">
                        <h1 className="text-[26px] font-bold tracking-[-0.03em] text-foreground">
                            {title}
                        </h1>
                        {description && (
                            <p className="text-[13.5px] text-muted-foreground">
                                {description}
                            </p>
                        )}
                    </div>
                </div>

                <div className="rounded-panel border border-border bg-card p-[26px] shadow-card">
                    {children}
                </div>
            </div>
        </div>
    );
}
