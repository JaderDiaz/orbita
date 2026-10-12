import {
    Bold,
    Code,
    Italic,
    Link2,
    List,
    ListOrdered,
    Minus,
    Quote,
    Strikethrough,
    Type,
    type LucideIcon,
} from 'lucide-react';
import { useRef, useState } from 'react';
import { renderMarkdown } from '@/features/clientes/markdown';
import { cn } from '@/lib/utils';

type Tool = {
    title: string;
    icon: LucideIcon;
    before: string;
    after?: string;
    line?: boolean;
};

const GROUPS: Tool[][] = [
    [
        { title: 'Negrita (⌘B)', icon: Bold, before: '**', after: '**' },
        { title: 'Cursiva (⌘I)', icon: Italic, before: '_', after: '_' },
        { title: 'Tachado', icon: Strikethrough, before: '~~', after: '~~' },
    ],
    [
        { title: 'Título', icon: Type, before: '# ', line: true },
        { title: 'Lista', icon: List, before: '- ', line: true },
        { title: 'Lista numerada', icon: ListOrdered, before: '1. ', line: true },
        { title: 'Cita', icon: Quote, before: '> ', line: true },
    ],
    [
        { title: 'Enlace', icon: Link2, before: '[', after: '](url)' },
        { title: 'Código', icon: Code, before: '`', after: '`' },
        { title: 'Separador', icon: Minus, before: '\n---\n', line: true },
    ],
];

/**
 * The prototype's notes editor: a toolbar that inserts Markdown, a write and a
 * preview tab, and a footer with the syntax reminder and the character count.
 *
 * The preview renders the same subset the server does. It is a convenience over
 * what the author just typed; what other people read is rendered server-side.
 */
export function NotasEditor({
    value,
    onChange,
}: {
    value: string;
    onChange: (value: string) => void;
}) {
    const [tab, setTab] = useState<'escribir' | 'preview'>('escribir');
    const textarea = useRef<HTMLTextAreaElement>(null);

    const apply = (tool: Tool): void => {
        const field = textarea.current;

        if (!field) {
            return;
        }

        const { selectionStart: start, selectionEnd: end } = field;
        const selected = value.slice(start, end);

        const inserted = tool.line
            ? `${tool.before}${selected}`
            : `${tool.before}${selected}${tool.after ?? ''}`;

        onChange(value.slice(0, start) + inserted + value.slice(end));

        requestAnimationFrame(() => {
            field.focus();
            const caret = start + tool.before.length + selected.length;
            field.setSelectionRange(caret, caret);
        });
    };

    return (
        <div className="border-input shadow-control overflow-hidden rounded-xl border bg-card transition-[border-color,box-shadow] focus-within:focus-ring-brand">
            <div className="border-surface-muted bg-surface-subtle flex flex-wrap items-center gap-0.5 border-b py-1.5 pr-1.5 pl-2">
                {GROUPS.map((group, index) => (
                    <div key={index} className="flex items-center gap-0.5">
                        {index > 0 && (
                            <span className="mx-1 h-4 w-px bg-black/10" />
                        )}
                        {group.map((tool) => (
                            <button
                                key={tool.title}
                                type="button"
                                title={tool.title}
                                aria-label={tool.title}
                                onMouseDown={(event) => {
                                    event.preventDefault();
                                    apply(tool);
                                }}
                                className="text-text-secondary hover:text-foreground flex h-7 w-[30px] items-center justify-center rounded-[7px] transition-colors hover:bg-card hover:shadow-[0_1px_2px_rgba(0,0,0,.08)]"
                            >
                                <tool.icon className="size-3.5" />
                            </button>
                        ))}
                    </div>
                ))}

                <div className="bg-surface-track ml-auto flex gap-0.5 rounded-lg p-0.5">
                    {(
                        [
                            ['escribir', 'Escribir'],
                            ['preview', 'Vista previa'],
                        ] as const
                    ).map(([key, label]) => (
                        <button
                            key={key}
                            type="button"
                            onClick={() => setTab(key)}
                            aria-pressed={tab === key}
                            className={cn(
                                'rounded-md px-2.5 py-1 text-[12px] font-medium transition-colors',
                                tab === key
                                    ? 'bg-card text-foreground shadow-[0_1px_2px_rgba(0,0,0,.08)]'
                                    : 'text-muted-foreground',
                            )}
                        >
                            {label}
                        </button>
                    ))}
                </div>
            </div>

            {tab === 'escribir' ? (
                <textarea
                    id="notas"
                    name="notas"
                    ref={textarea}
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                    rows={5}
                    placeholder={
                        'Ej. **Requiere orden de compra** antes de facturar.\n- Contacto de pagos: tesoreria@empresa.co\n- Radicar facturas los primeros 5 días del mes'
                    }
                    className="block min-h-[120px] w-full resize-y border-none bg-card px-3.5 py-3 font-mono text-[13.5px] leading-relaxed outline-none"
                />
            ) : (
                <>
                    <input type="hidden" name="notas" value={value} />
                    <div
                        className="markdown min-h-[120px] px-4 py-3 text-[13.5px] leading-relaxed"
                        // Escaped before any markup is built, and link schemes
                        // are allowlisted. See features/clientes/markdown.ts.
                        dangerouslySetInnerHTML={{
                            __html:
                                renderMarkdown(value) ||
                                '<p class="text-text-tertiary">Nada que previsualizar todavía.</p>',
                        }}
                    />
                </>
            )}

            <div className="border-surface-muted text-text-tertiary flex items-center justify-between gap-3 border-t px-3.5 py-[7px] text-[11.5px]">
                <span className="font-mono">
                    **negrita** · _cursiva_ · - lista · [enlace](url)
                </span>
                <span>
                    {value.length}{' '}
                    {value.length === 1 ? 'carácter' : 'caracteres'}
                </span>
            </div>
        </div>
    );
}
