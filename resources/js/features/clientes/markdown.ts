/**
 * The same Markdown subset the server renders, for the editor's preview tab.
 *
 * This is a convenience over text the author just typed, never a trust
 * boundary: what other people read is rendered by SafeMarkdown on the server.
 * It still escapes first and allowlists link schemes, so the two agree on what
 * a note will look like once saved.
 */
const SAFE_SCHEMES = ['http://', 'https://', 'mailto:'];

function escapeHtml(value: string): string {
    return value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function links(html: string): string {
    return html.replace(
        /\[([^\]]+)\]\(([^)\s]+)\)/g,
        (whole, text: string, url: string) => {
            const plain = url.replace(/&amp;/g, '&').replace(/&quot;/g, '"');

            if (!SAFE_SCHEMES.some((scheme) => plain.toLowerCase().startsWith(scheme))) {
                return whole;
            }

            return `<a href="${escapeHtml(plain)}" target="_blank" rel="noopener noreferrer">${text}</a>`;
        },
    );
}

function inline(escaped: string): string {
    const html = escaped
        .replace(/`([^`]+)`/g, '<code>$1</code>')
        .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
        .replace(/(?<!\w)_([^_]+)_(?!\w)/g, '<em>$1</em>')
        .replace(/~~([^~]+)~~/g, '<del>$1</del>');

    return links(html);
}

export function renderMarkdown(markdown: string): string {
    if (markdown.trim() === '') {
        return '';
    }

    const out: string[] = [];
    let list: 'ul' | 'ol' | null = null;

    const closeList = (): string => (list === null ? '' : `</${list}>`);

    for (const line of markdown.split(/\r\n|\r|\n/)) {
        const escaped = escapeHtml(line).trim();

        if (escaped === '') {
            out.push(closeList());
            list = null;
            continue;
        }

        const heading = /^(#{1,3})\s+(.*)$/.exec(escaped);
        if (heading) {
            out.push(closeList());
            list = null;
            const level = heading[1].length + 2;
            out.push(`<h${level}>${inline(heading[2])}</h${level}>`);
            continue;
        }

        const quote = /^&gt;\s?(.*)$/.exec(escaped);
        if (quote) {
            out.push(closeList());
            list = null;
            out.push(`<blockquote>${inline(quote[1])}</blockquote>`);
            continue;
        }

        const bullet = /^[-*]\s+(.*)$/.exec(escaped);
        if (bullet) {
            out.push(list === 'ul' ? '' : `${closeList()}<ul>`);
            list = 'ul';
            out.push(`<li>${inline(bullet[1])}</li>`);
            continue;
        }

        const numbered = /^\d+\.\s+(.*)$/.exec(escaped);
        if (numbered) {
            out.push(list === 'ol' ? '' : `${closeList()}<ol>`);
            list = 'ol';
            out.push(`<li>${inline(numbered[1])}</li>`);
            continue;
        }

        out.push(closeList());
        list = null;
        out.push(`<p>${inline(escaped)}</p>`);
    }

    out.push(closeList());

    return out.filter(Boolean).join('');
}
