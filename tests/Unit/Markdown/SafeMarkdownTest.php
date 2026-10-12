<?php

namespace Tests\Unit\Markdown;

use App\Support\Markdown\SafeMarkdown;
use PHPUnit\Framework\TestCase;

/**
 * Notes are user input rendered back to other users, so the renderer is a trust
 * boundary. The prototype's own converter emitted raw hrefs, which accepts
 * javascript: URLs.
 */
class SafeMarkdownTest extends TestCase
{
    public function test_it_renders_the_supported_marks(): void
    {
        $this->assertSame('<p><strong>Negrita</strong></p>', SafeMarkdown::toHtml('**Negrita**'));
        $this->assertSame('<p><em>Cursiva</em></p>', SafeMarkdown::toHtml('_Cursiva_'));
        $this->assertSame('<p><del>Tachado</del></p>', SafeMarkdown::toHtml('~~Tachado~~'));
        $this->assertSame('<p><code>codigo</code></p>', SafeMarkdown::toHtml('`codigo`'));
        $this->assertSame('<h3>Titulo</h3>', SafeMarkdown::toHtml('# Titulo'));
        $this->assertSame('<blockquote>Cita</blockquote>', SafeMarkdown::toHtml('> Cita'));
    }

    public function test_it_renders_lists(): void
    {
        $this->assertSame('<ul><li>Uno</li><li>Dos</li></ul>', SafeMarkdown::toHtml("- Uno\n- Dos"));
        $this->assertSame('<ol><li>Uno</li></ol>', SafeMarkdown::toHtml('1. Uno'));
    }

    public function test_html_the_user_typed_is_escaped_not_rendered(): void
    {
        $this->assertSame(
            '<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>',
            SafeMarkdown::toHtml('<script>alert(1)</script>'),
        );

        $this->assertSame(
            '<p>&lt;img src=x onerror=alert(1)&gt;</p>',
            SafeMarkdown::toHtml('<img src=x onerror=alert(1)>'),
        );
    }

    public function test_a_safe_link_becomes_an_anchor(): void
    {
        $this->assertSame(
            '<p><a href="https://orbita.co" target="_blank" rel="noopener noreferrer">Orbita</a></p>',
            SafeMarkdown::toHtml('[Orbita](https://orbita.co)'),
        );
    }

    public function test_an_unsafe_scheme_is_left_as_text(): void
    {
        foreach (['javascript:alert(1)', 'data:text/html;base64,PHN2Zz4=', 'vbscript:msgbox'] as $url) {
            $html = SafeMarkdown::toHtml("[Click]({$url})");

            $this->assertStringNotContainsString('<a ', $html, "El esquema [{$url}] no debe producir un enlace.");
            $this->assertStringNotContainsString('href', $html);
        }
    }

    public function test_a_quote_cannot_break_out_of_an_attribute(): void
    {
        $html = SafeMarkdown::toHtml('[x](https://orbita.co" onmouseover="alert(1))');

        $this->assertStringNotContainsString('onmouseover="alert(1)"', $html);
    }

    public function test_empty_input_renders_nothing(): void
    {
        $this->assertSame('', SafeMarkdown::toHtml(null));
        $this->assertSame('', SafeMarkdown::toHtml('   '));
    }
}
