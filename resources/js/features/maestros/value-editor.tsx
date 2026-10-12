import { Form } from '@inertiajs/react';
import { RotateCcw } from 'lucide-react';
import { useState } from 'react';
import MaestrosController from '@/actions/App/Http/Controllers/MaestrosController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { CatalogBadge, CodeChip } from '@/features/maestros/catalog-badge';
import { CatalogIcon } from '@/features/maestros/catalog-icon';
import type {
    CatalogList,
    CatalogValue,
    IconEntry,
    PaletteEntry,
} from '@/features/maestros/types';
import { cn } from '@/lib/utils';

const FLAT_BADGE = { background: '#f2f2f7', foreground: '#48484a' };

function Toggle({
    name,
    checked,
    onChange,
    label,
    hint,
}: {
    name: string;
    checked: boolean;
    onChange: (next: boolean) => void;
    label: string;
    hint?: string;
}) {
    return (
        <div className="border-input rounded-md border px-3 py-2">
            <input type="hidden" name={name} value={checked ? '1' : '0'} />
            <button
                type="button"
                role="switch"
                aria-checked={checked}
                onClick={() => onChange(!checked)}
                className="flex w-full items-center justify-between gap-3 text-left"
            >
                <span>
                    <span className="text-text-secondary block text-[12.5px] font-medium">
                        {label}
                    </span>
                    {hint && (
                        <span className="text-text-tertiary block text-[11.5px]">
                            {hint}
                        </span>
                    )}
                </span>
                <span
                    className={cn(
                        'relative h-[22px] w-[38px] shrink-0 rounded-full transition-colors',
                        checked ? 'bg-[#34c759]' : 'bg-[#e5e5ea]',
                    )}
                >
                    <span
                        className={cn(
                            'absolute top-0.5 size-[18px] rounded-full bg-white shadow transition-all',
                            checked ? 'left-[18px]' : 'left-0.5',
                        )}
                    />
                </span>
            </button>
        </div>
    );
}

/**
 * Creating and editing happen in place, as the prototype does it, so the row
 * being changed stays on screen.
 */
export function ValueEditor({
    list,
    value,
    palette,
    icons,
    onCancel,
}: {
    list: CatalogList;
    value: CatalogValue | null;
    palette: PaletteEntry[];
    icons: IconEntry[];
    onCancel: () => void;
}) {
    const [name, setName] = useState(value?.name ?? '');
    const [code, setCode] = useState(value?.code ?? '');
    const [description, setDescription] = useState(value?.description ?? '');
    const [color, setColor] = useState(value?.color ?? null);
    const [icon, setIcon] = useState(value?.icon ?? null);
    const [flag, setFlag] = useState(value?.flag ?? false);
    const [isDefault, setIsDefault] = useState(value?.is_default ?? false);

    const action = value
        ? MaestrosController.update.form({ value: value.id })
        : MaestrosController.store.form({ list: list.id });

    const selectedColor = palette.find((entry) => entry.key === color);
    const pair = selectedColor
        ? {
              background: selectedColor.background,
              foreground: selectedColor.foreground,
          }
        : FLAT_BADGE;

    // Mirrors the server's badge rule for the preview of a value that has not
    // been saved yet: a list with codes but no colours shows the code.
    const showsCode =
        list.fields.includes('code') && !list.fields.includes('color');
    const previewText =
        (showsCode ? code.trim() : name.trim()) || name.trim() || 'Nuevo valor';

    return (
        <section className="rounded-card border-brand-border bg-card border p-5 shadow-[0_0_0_4px_rgba(249,115,22,.08)]">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <h3 className="text-[15px] font-semibold">
                    {value
                        ? `Editar «${value.name}»`
                        : `Nuevo valor en ${list.title}`}
                </h3>
                <div className="flex items-center gap-2">
                    <span className="text-text-tertiary text-[11px] font-semibold tracking-[0.04em] uppercase">
                        Vista previa
                    </span>
                    <CatalogBadge
                        badge={{ ...pair, text: previewText }}
                        icon={icon}
                    />
                    {list.fields.includes('code') && code.trim() !== '' && (
                        <CodeChip code={code.trim()} />
                    )}
                </div>
            </div>

            <Form
                {...action}
                options={{ preserveScroll: true }}
                onSuccess={onCancel}
                className="mt-4 space-y-4"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-1.5">
                                <Label htmlFor="name">Nombre</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    value={name}
                                    onChange={(event) =>
                                        setName(event.target.value)
                                    }
                                    placeholder="Nombre del valor"
                                    autoFocus
                                />
                                <InputError message={errors.name} />
                            </div>

                            {list.fields.includes('code') && (
                                <div className="grid gap-1.5">
                                    <Label htmlFor="code">
                                        Código corto
                                        <span className="text-text-tertiary font-normal">
                                            {' '}
                                            · hasta 5
                                        </span>
                                    </Label>
                                    <Input
                                        id="code"
                                        name="code"
                                        value={code}
                                        maxLength={5}
                                        onChange={(event) =>
                                            setCode(
                                                event.target.value.toUpperCase(),
                                            )
                                        }
                                        placeholder="Ej. NIT"
                                        className="font-mono uppercase"
                                    />
                                    <InputError message={errors.code} />
                                </div>
                            )}

                            {list.fields.includes('desc') && (
                                <div className="grid gap-1.5 sm:col-span-2">
                                    <Label htmlFor="description">
                                        Descripción
                                        <span className="text-text-tertiary font-normal">
                                            {' '}
                                            · opcional
                                        </span>
                                    </Label>
                                    <Input
                                        id="description"
                                        name="description"
                                        value={description}
                                        onChange={(event) =>
                                            setDescription(event.target.value)
                                        }
                                        placeholder="Ayuda que se muestra junto al valor"
                                    />
                                    <InputError message={errors.description} />
                                </div>
                            )}

                            {list.fields.includes('color') && (
                                <div className="grid gap-1.5 sm:col-span-2">
                                    <Label>Color del badge</Label>
                                    <input
                                        type="hidden"
                                        name="color"
                                        value={color ?? ''}
                                    />
                                    <div className="flex flex-wrap items-center gap-2">
                                        {palette.map((entry) => (
                                            <button
                                                key={entry.key}
                                                type="button"
                                                title={entry.label}
                                                aria-label={entry.label}
                                                aria-pressed={
                                                    color === entry.key
                                                }
                                                onClick={() =>
                                                    setColor(entry.key)
                                                }
                                                style={{
                                                    background:
                                                        entry.background,
                                                }}
                                                className={cn(
                                                    'flex size-7 items-center justify-center rounded-full transition-shadow',
                                                    color === entry.key &&
                                                        'ring-2 ring-offset-2',
                                                )}
                                            >
                                                <span
                                                    className="size-2.5 rounded-full"
                                                    style={{
                                                        background:
                                                            entry.foreground,
                                                    }}
                                                />
                                            </button>
                                        ))}
                                    </div>
                                    <InputError message={errors.color} />
                                </div>
                            )}

                            {list.fields.includes('icon') && (
                                <div className="grid gap-1.5 sm:col-span-2">
                                    <Label>Icono</Label>
                                    <input
                                        type="hidden"
                                        name="icon"
                                        value={icon ?? ''}
                                    />
                                    <div className="flex flex-wrap gap-2">
                                        {icons.map((entry) => (
                                            <button
                                                key={entry.key}
                                                type="button"
                                                title={entry.label}
                                                aria-label={entry.label}
                                                aria-pressed={icon === entry.key}
                                                onClick={() =>
                                                    setIcon(entry.key)
                                                }
                                                className={cn(
                                                    'border-input flex size-[34px] items-center justify-center rounded-md border transition-colors',
                                                    icon === entry.key
                                                        ? 'border-brand bg-brand-surface text-brand-text'
                                                        : 'text-text-secondary hover:bg-surface-subtle',
                                                )}
                                            >
                                                <CatalogIcon
                                                    icon={entry.key}
                                                    className="size-4"
                                                />
                                            </button>
                                        ))}
                                    </div>
                                    <InputError message={errors.icon} />
                                </div>
                            )}

                            {list.flag && (
                                <Toggle
                                    name="flag"
                                    checked={flag}
                                    onChange={setFlag}
                                    label={list.flag.label}
                                />
                            )}

                            <Toggle
                                name="is_default"
                                checked={isDefault}
                                onChange={setIsDefault}
                                label="Predeterminado"
                                hint="Se preselecciona en formularios"
                            />
                        </div>

                        <div className="flex items-center gap-2">
                            <Button
                                type="submit"
                                disabled={processing || name.trim() === ''}
                            >
                                {processing && <Spinner />}
                                Guardar valor
                            </Button>
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={onCancel}
                            >
                                Cancelar
                            </Button>
                            {value && (
                                <span className="text-text-tertiary ml-auto inline-flex items-center gap-1 text-[11.5px]">
                                    <RotateCcw className="size-3" />
                                    Los registros existentes conservan su valor
                                </span>
                            )}
                        </div>
                    </>
                )}
            </Form>
        </section>
    );
}
