<?php

namespace App\Support\Markdown;

/**
 * Renders a small Markdown subset to HTML that is safe by construction.
 *
 * The input is escaped FIRST and the markup is built from the escaped text, so
 * no tag a user typed can survive. Links are the only element carrying a URL
 * and their scheme is checked against an allowlist: the prototype's own
 * converter emitted raw hrefs, which accepts `javascript:`.
 *
 * Deliberately small. Notes are internal annotations, not documents, and every
 * element added here is another thing to get right.
 */
class SafeMarkdown
{
    /**
     * Schemes a link may use. Anything else renders as plain text.
     *
     * @var list<string>
     */
    private const SAFE_SCHEMES = ['http://', 'https://', 'mailto:'];

    public static function toHtml(?string $markdown): string
    {
        if ($markdown === null || trim($markdown) === '') {
            return '';
        }

        $lines = preg_split('/\r\n|\r|\n/', $markdown) ?: [];

        $html = [];
        $listType = null;

        foreach ($lines as $line) {
            $escaped = htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $trimmed = trim($escaped);

            if ($trimmed === '') {
                $html[] = self::closeList($listType);
                $listType = null;

                continue;
            }

            if (preg_match('/^(#{1,3})\s+(.*)$/', $trimmed, $heading) === 1) {
                $html[] = self::closeList($listType);
                $listType = null;

                $level = strlen($heading[1]) + 2;
                $html[] = "<h{$level}>".self::inline($heading[2])."</h{$level}>";

                continue;
            }

            if (preg_match('/^&gt;\s?(.*)$/', $trimmed, $quote) === 1) {
                $html[] = self::closeList($listType);
                $listType = null;
                $html[] = '<blockquote>'.self::inline($quote[1]).'</blockquote>';

                continue;
            }

            if (preg_match('/^[-*]\s+(.*)$/', $trimmed, $item) === 1) {
                $html[] = $listType === 'ul' ? '' : self::closeList($listType).'<ul>';
                $listType = 'ul';
                $html[] = '<li>'.self::inline($item[1]).'</li>';

                continue;
            }

            if (preg_match('/^\d+\.\s+(.*)$/', $trimmed, $item) === 1) {
                $html[] = $listType === 'ol' ? '' : self::closeList($listType).'<ol>';
                $listType = 'ol';
                $html[] = '<li>'.self::inline($item[1]).'</li>';

                continue;
            }

            $html[] = self::closeList($listType);
            $listType = null;
            $html[] = '<p>'.self::inline($trimmed).'</p>';
        }

        $html[] = self::closeList($listType);

        return implode('', array_filter($html));
    }

    private static function closeList(?string $listType): string
    {
        return $listType === null ? '' : "</{$listType}>";
    }

    /**
     * Inline marks, applied to already-escaped text.
     */
    private static function inline(string $escaped): string
    {
        $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $escaped) ?? $escaped;
        $html = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $html) ?? $html;
        $html = preg_replace('/(?<!\w)_([^_]+)_(?!\w)/', '<em>$1</em>', $html) ?? $html;
        $html = preg_replace('/~~([^~]+)~~/', '<del>$1</del>', $html) ?? $html;

        return self::links($html);
    }

    /**
     * `[text](url)`, but only when the scheme is one we allow. A rejected link
     * keeps its literal text so nothing the user wrote disappears silently.
     */
    private static function links(string $html): string
    {
        return preg_replace_callback(
            '/\[([^\]]+)\]\(([^)\s]+)\)/',
            function (array $match): string {
                $url = html_entity_decode($match[2], ENT_QUOTES, 'UTF-8');

                foreach (self::SAFE_SCHEMES as $scheme) {
                    if (stripos($url, $scheme) === 0) {
                        $href = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                        return '<a href="'.$href.'" target="_blank" rel="noopener noreferrer">'.$match[1].'</a>';
                    }
                }

                return $match[0];
            },
            $html,
        ) ?? $html;
    }
}
