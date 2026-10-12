import type { HTMLAttributes } from 'react';

/**
 * The Orbita mark: an "O" on the prototype's orange gradient.
 *
 * Rendered as a div rather than an SVG because the gradient, inner highlight
 * and glow are the identity, and they are all declared verbatim from
 * `Prototipo Login.dc.html`.
 */
export default function AppLogoIcon({
    className,
    style,
    ...props
}: HTMLAttributes<HTMLDivElement>) {
    return (
        <div
            {...props}
            className={className}
            style={{
                backgroundImage:
                    'linear-gradient(160deg, #fdba74 0%, #f97316 55%, #ea580c 100%)',
                boxShadow:
                    '0 6px 16px -4px rgba(249,115,22,.5), inset 0 1px 0 rgba(255,255,255,.35)',
                ...style,
            }}
        >
            O
        </div>
    );
}
