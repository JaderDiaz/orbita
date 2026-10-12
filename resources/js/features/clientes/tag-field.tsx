import { RotateCcw } from 'lucide-react';
import { cn } from '@/lib/utils';

/**
 * The numbering tag.
 *
 * A single bordered control holding the `#` prefix, a three-character monospace
 * input and, once the user has typed their own, a button to go back to the one
 * derived from the name. The help line shows what this client's documents will
 * be numbered as, because that is the consequence of the field.
 */
export function TagField({
    value,
    onChange,
    onReset,
}: {
    value: string;
    onChange: (value: string) => void;
    onReset: () => void;
}) {
    const manual = value.trim() !== '';
    const tag = value.trim().toUpperCase();

    return (
        <>
            <div className="border-input shadow-control focus-within:focus-ring-brand flex items-stretch overflow-hidden rounded-lg border bg-card transition-[border-color,box-shadow]">
                <span className="bg-brand-surface border-brand-border text-brand-text flex items-center border-r px-2.5 text-[13px] font-bold">
                    #
                </span>
                <input
                    id="tag"
                    name="tag"
                    type="text"
                    maxLength={3}
                    value={value}
                    onChange={(event) =>
                        onChange(event.target.value.toUpperCase())
                    }
                    placeholder="ABC"
                    className="w-full min-w-0 border-none bg-transparent px-2.5 py-2.5 font-mono text-[14px] font-semibold tracking-[0.18em] uppercase outline-none"
                />
                {manual && (
                    <button
                        type="button"
                        onClick={onReset}
                        title="Volver a generar desde el nombre"
                        aria-label="Volver a generar desde el nombre"
                        className="text-text-tertiary hover:bg-brand-surface hover:text-brand-text flex items-center px-2.5 transition-colors"
                    >
                        <RotateCcw className="size-3.5" />
                    </button>
                )}
            </div>
            <span className="text-text-tertiary font-mono text-[11px]">
                {tag.length === 3
                    ? `FAC-${tag}-0001 · COT-${tag}-0001`
                    : 'Se usa en la numeración de facturas y cotizaciones'}
            </span>
        </>
    );
}

/**
 * The badge beside the label: the tag is derived from the name until the user
 * takes it over.
 */
export function TagBadge({ value }: { value: string }) {
    const manual = value.trim() !== '';

    return (
        <span
            className={cn(
                'rounded-full px-[7px] py-px text-[10.5px] font-semibold',
                manual
                    ? 'bg-[#e8f1ff] text-[#0a64d6]'
                    : 'bg-surface-muted text-text-secondary',
            )}
        >
            {manual ? 'Editado' : 'Auto'}
        </span>
    );
}
