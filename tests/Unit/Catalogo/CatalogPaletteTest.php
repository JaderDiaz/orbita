<?php

namespace Tests\Unit\Catalogo;

use App\Enums\CatalogColor;
use App\Enums\CatalogIcon;
use PHPUnit\Framework\TestCase;

/**
 * The palette and the icon set feed database check constraints, so their values
 * are part of the schema, not decoration. No database needed to assert them.
 */
class CatalogPaletteTest extends TestCase
{
    public function test_the_palette_is_the_prototypes_eight_pairs(): void
    {
        $this->assertSame(
            ['gris', 'azul', 'verde', 'naranja', 'rojo', 'morado', 'turquesa', 'indigo'],
            CatalogColor::values(),
        );
    }

    public function test_every_colour_resolves_a_distinct_badge_pair(): void
    {
        $pairs = array_map(
            fn (CatalogColor $color): string => $color->background().'/'.$color->foreground(),
            CatalogColor::cases(),
        );

        $this->assertCount(count(CatalogColor::cases()), array_unique($pairs));

        foreach (CatalogColor::cases() as $color) {
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $color->background());
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $color->foreground());
            $this->assertNotSame($color->background(), $color->foreground(), 'Un badge ilegible no es un badge.');
        }
    }

    public function test_the_icon_set_matches_the_prototypes_picker(): void
    {
        $this->assertSame([
            'sparkle', 'bug', 'up', 'down', 'minus', 'flag', 'file', 'pen',
            'check', 'lock', 'box', 'card', 'bank', 'cash', 'tag', 'clock',
        ], CatalogIcon::values());
    }

    public function test_every_colour_and_icon_carries_a_spanish_label(): void
    {
        foreach (CatalogColor::cases() as $color) {
            $this->assertNotSame('', $color->label());
        }

        foreach (CatalogIcon::cases() as $icon) {
            $this->assertNotSame('', $icon->label());
        }
    }
}
