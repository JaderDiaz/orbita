<?php

namespace Tests\Unit\Catalogo;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * The agency renames catalog values freely, so a rename must never change what
 * the code does. Behaviour keys off `system_key` through CatalogValue::matches()
 * and the flags jsonb, never off the display name.
 *
 * Scoped to app/ on purpose. That is where branching lives; the seeder has to
 * carry the names because it writes them, and the frontend has to render them.
 */
class CatalogArchitectureTest extends TestCase
{
    /**
     * Display names of the values the code owns. If one of these appears as a
     * literal under app/, something is comparing against a name.
     *
     * @var list<string>
     */
    private const CODE_OWNED_NAMES = [
        'Aprobada',
        'Aceptada',
        'Hecho',
        'Pagada',
        'Responsable de IVA',
        'Precio cerrado',
    ];

    public function test_no_catalog_value_name_is_compared_with_a_literal(): void
    {
        foreach ($this->phpFilesUnderApp() as $path => $contents) {
            foreach (self::CODE_OWNED_NAMES as $name) {
                $this->assertStringNotContainsString(
                    "'{$name}'",
                    $contents,
                    "[{$path}] menciona el nombre «{$name}». Usa CatalogValue::matches() con su system_key."
                );
            }
        }
    }

    public function test_nothing_branches_on_a_catalog_value_name_property(): void
    {
        foreach ($this->phpFilesUnderApp() as $path => $contents) {
            if (! str_contains($contents, 'CatalogValue')) {
                continue;
            }

            $this->assertSame(
                0,
                preg_match('/->name\s*(===|==|!==|!=)/', $contents),
                "[{$path}] compara ->name de un valor del catalogo. Usa matches() o flag()."
            );
        }
    }

    /**
     * @return array<string, string>
     */
    private function phpFilesUnderApp(): array
    {
        $root = dirname(__DIR__, 3).'/app';

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
        $contents = [];

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relative = str_replace(dirname(__DIR__, 3).'/', '', $file->getPathname());
                $contents[$relative] = (string) file_get_contents($file->getPathname());
            }
        }

        $this->assertNotEmpty($contents, 'No se encontraron archivos PHP en app/.');

        return $contents;
    }
}
