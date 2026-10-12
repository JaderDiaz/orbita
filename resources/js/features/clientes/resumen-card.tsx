import { Check } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import type { ClienteFormValues } from '@/features/clientes/types';
import { cn } from '@/lib/utils';

type ChecklistItem = { label: string; done: boolean };

function Row({
    label,
    value,
    mono,
}: {
    label: string;
    value: string;
    mono?: boolean;
}) {
    return (
        <div className="flex items-baseline justify-between gap-3">
            <span className="text-muted-foreground shrink-0">{label}</span>
            <span
                className={cn(
                    'min-w-0 truncate text-right',
                    mono && 'font-mono text-[12px]',
                )}
            >
                {value}
            </span>
        </div>
    );
}

/**
 * The form's sticky summary: what the client will look like once saved, and
 * what is still missing before it can be.
 */
export function ResumenCard({
    values,
    tipoDocumento,
    regimen,
    iniciales,
    nombreLabel,
    checklist,
    processing,
    editing,
    onCancel,
}: {
    values: ClienteFormValues;
    tipoDocumento: string;
    regimen: string;
    iniciales: string;
    nombreLabel: string;
    checklist: ChecklistItem[];
    processing: boolean;
    editing: boolean;
    onCancel: () => void;
}) {
    const listos = checklist.filter((item) => item.done).length;
    const completo = listos === checklist.length;
    const tag = values.tag.trim().toUpperCase() || '———';
    const principal = values.contactos.find((contacto) => contacto.is_principal);

    return (
        <div className="bg-card rounded-card border-border shadow-card overflow-hidden border">
            <div className="border-surface-muted flex items-center gap-3 border-b bg-linear-to-b from-[#fffaf5] to-card px-5 pt-[18px] pb-4">
                <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-linear-[160deg,#fdba74,#f97316] text-[15px] font-bold text-white shadow-[inset_0_1px_0_rgba(255,255,255,.3),0_4px_10px_-4px_rgba(249,115,22,.5)]">
                    {iniciales}
                </span>
                <div className="flex min-w-0 flex-col gap-[3px]">
                    <span className="truncate text-[14px] font-semibold tracking-[-0.01em]">
                        {values.nombre.trim() || nombreLabel}
                    </span>
                    <div className="flex items-center gap-1.5">
                        <span className="bg-foreground rounded-md px-[7px] py-0.5 font-mono text-[10.5px] font-bold tracking-[0.12em] text-white">
                            #{tag}
                        </span>
                        <span className="text-muted-foreground text-[11.5px]">
                            Persona{' '}
                            {values.tipo_persona === 'juridica'
                                ? 'Jurídica'
                                : 'Natural'}
                        </span>
                    </div>
                </div>
            </div>

            <div className="flex flex-col gap-[9px] px-5 py-4 text-[12.5px]">
                <Row
                    label={tipoDocumento || 'Documento'}
                    value={values.documento.trim() || '—'}
                    mono
                />
                <Row label="Régimen" value={regimen || '—'} />
                <Row
                    label="Ciudad"
                    value={
                        [values.ciudad.trim(), values.departamento.trim()]
                            .filter(Boolean)
                            .join(', ') || '—'
                    }
                />
                <div className="flex items-center justify-between gap-3">
                    <span className="text-muted-foreground">Estado</span>
                    <span
                        className="rounded-full px-[9px] py-0.5 text-[11px] font-semibold"
                        style={
                            values.is_active
                                ? { background: '#e8f7ee', color: '#1a7f37' }
                                : { background: '#f0f0f2', color: '#6e6e73' }
                        }
                    >
                        {values.is_active ? 'Activo' : 'Inactivo'}
                    </span>
                </div>

                <div className="border-surface-muted flex flex-col gap-[3px] border-t pt-2.5">
                    <span className="text-muted-foreground">
                        Contacto principal
                    </span>
                    <span className="truncate font-medium">
                        {principal?.nombre.trim() || '—'}
                    </span>
                    <span className="text-text-tertiary truncate text-[12px]">
                        {principal?.email.trim() || '—'}
                    </span>
                    <span className="text-text-tertiary text-[11.5px]">
                        {values.contactos.length} en total
                    </span>
                </div>

                <div className="border-surface-muted flex flex-col gap-1.5 border-t pt-2.5">
                    <span className="text-muted-foreground">Numeración</span>
                    <div className="flex flex-wrap gap-1.5">
                        <span className="bg-surface-muted text-text-secondary rounded-md px-2 py-[3px] font-mono text-[11px]">
                            FAC-{tag}-0001
                        </span>
                        <span className="bg-surface-muted text-text-secondary rounded-md px-2 py-[3px] font-mono text-[11px]">
                            COT-{tag}-0001
                        </span>
                    </div>
                </div>
            </div>

            <div className="border-surface-muted flex flex-col gap-2.5 border-t px-5 pt-3.5 pb-[18px]">
                <div className="flex justify-between text-[12px]">
                    <span className="text-muted-foreground">
                        Campos obligatorios
                    </span>
                    <span className="tabular font-semibold">
                        {listos} de {checklist.length}
                    </span>
                </div>

                <div className="bg-surface-muted h-[5px] rounded-[3px]">
                    <div
                        className="h-[5px] rounded-[3px] bg-linear-to-r from-[#fdba74] to-[#f97316] transition-[width] duration-300"
                        style={{
                            width: `${(listos / checklist.length) * 100}%`,
                        }}
                    />
                </div>

                <div className="flex flex-col gap-1.5">
                    {checklist.map((item) => (
                        <div
                            key={item.label}
                            className={cn(
                                'flex items-center gap-2 text-[12px]',
                                item.done
                                    ? 'text-foreground'
                                    : 'text-text-tertiary',
                            )}
                        >
                            <span
                                className={cn(
                                    'flex size-[15px] shrink-0 items-center justify-center rounded-full border-[1.5px]',
                                    item.done
                                        ? 'border-[#1a7f37] bg-[#1a7f37] text-white'
                                        : 'border-[#d1d1d6] bg-card',
                                )}
                            >
                                {item.done && (
                                    <Check className="size-2.5" strokeWidth={3} />
                                )}
                            </span>
                            {item.label}
                        </div>
                    ))}
                </div>

                <div className="flex flex-col gap-2 pt-1.5">
                    <Button type="submit" disabled={processing || !completo}>
                        {processing && <Spinner />}
                        {editing ? 'Guardar cambios' : 'Guardar cliente'}
                    </Button>
                    <Button type="button" variant="secondary" onClick={onCancel}>
                        Cancelar
                    </Button>
                </div>
            </div>
        </div>
    );
}
