import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { cn } from '@/lib/utils';

/**
 * One field of the form: a 12.5px label, the control, and its error.
 *
 * `span` maps to the prototype's grid, which is an auto-fill of 210px columns
 * where the name takes two and the wide fields take the whole row.
 */
export function Field({
    label,
    htmlFor,
    hint,
    aside,
    span = 1,
    error,
    children,
}: {
    label: string;
    htmlFor?: string;
    hint?: string;
    aside?: ReactNode;
    span?: 1 | 2 | 'full';
    error?: string;
    children: ReactNode;
}) {
    return (
        <div
            className={cn(
                'flex min-w-0 flex-col gap-1.5',
                span === 2 && 'col-span-2',
                span === 'full' && 'col-span-full',
            )}
        >
            <div className="flex items-baseline justify-between gap-2.5">
                <label
                    htmlFor={htmlFor}
                    className="text-text-secondary text-[12.5px] font-medium"
                >
                    {label}
                </label>
                {aside}
                {hint && (
                    <span className="text-text-tertiary text-[11.5px]">
                        {hint}
                    </span>
                )}
            </div>
            {children}
            <InputError message={error} />
        </div>
    );
}
