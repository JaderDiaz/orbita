import { cn } from '@/lib/utils';

/**
 * The prototype's segmented control: a recessed track with the selected option
 * raised on white. Used for tipo de persona and estado.
 */
export function Segmented({
    name,
    value,
    options,
    onChange,
}: {
    name: string;
    value: string;
    options: { value: string; label: string }[];
    onChange: (value: string) => void;
}) {
    return (
        <div className="bg-surface-track flex gap-0.5 rounded-[11px] p-[3px] shadow-[inset_0_1px_2px_rgba(0,0,0,.04)]">
            <input type="hidden" name={name} value={value} />
            {options.map((option) => (
                <button
                    key={option.value}
                    type="button"
                    onClick={() => onChange(option.value)}
                    aria-pressed={value === option.value}
                    className={cn(
                        'flex-1 rounded-md py-1.5 text-center text-[12.5px] font-medium transition-colors',
                        value === option.value
                            ? 'bg-card text-foreground shadow-[0_1px_2px_rgba(0,0,0,.08)]'
                            : 'text-muted-foreground',
                    )}
                >
                    {option.label}
                </button>
            ))}
        </div>
    );
}
