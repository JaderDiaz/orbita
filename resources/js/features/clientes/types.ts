export type ClienteContacto = {
    id: number | null;
    nombre: string;
    cargo: string;
    email: string;
    telefono: string;
    is_principal: boolean;
};

export type ClienteRow = {
    id: number;
    nombre: string;
    iniciales: string;
    documento: string;
    tag: string;
    is_active: boolean;
    contacto: { nombre: string; email: string } | null;
};

export type ClienteDetail = ClienteRow & {
    tipo_persona: string;
    tipo_persona_label: string;
    tipo_documento: string;
    regimen: string;
    ciudad: string;
    departamento: string;
    direccion: string | null;
    notas: string | null;
    notas_html: string;
    numeracion: { factura: string; cotizacion: string };
    desde: string | null;
    usos: number;
    contactos: ClienteContacto[];
};

export type ClienteFormValues = {
    id: number | null;
    tipo_persona: string;
    tipo_documento_value_id: number | null;
    regimen_value_id: number | null;
    documento: string;
    nombre: string;
    tag: string;
    ciudad: string;
    departamento: string;
    direccion: string;
    notas: string;
    is_active: boolean;
    contactos: ClienteContacto[];
};

export type CatalogOption = {
    id: number;
    name: string;
    code: string | null;
    description: string | null;
    is_default: boolean;
};

export type ClienteOptions = {
    tipos_documento: CatalogOption[];
    regimenes: CatalogOption[];
};

export type PaginatorLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginator<T> = {
    data: T[];
    links: PaginatorLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number | null;
    to: number | null;
    total: number;
};

/**
 * "1–5 de 6 clientes", as the prototype writes it.
 */
export function paginationSummary(paginator: Paginator<unknown>): string {
    if (paginator.total === 0) {
        return 'Sin resultados';
    }

    const noun = paginator.total === 1 ? 'cliente' : 'clientes';

    return `${paginator.from}–${paginator.to} de ${paginator.total} ${noun}`;
}
