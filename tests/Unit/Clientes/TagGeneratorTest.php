<?php

namespace Tests\Unit\Clientes;

use App\Support\Clientes\TagGenerator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The tag becomes the prefix of a client's invoice and quote numbering, so it
 * is fiscal data derived from a name. No database involved.
 */
class TagGeneratorTest extends TestCase
{
    /**
     * Two words take the first TWO letters of the first plus the initial of the
     * second, which is the prototype's rule and not the obvious one.
     */
    public function test_two_words_follow_the_prototypes_rule(): void
    {
        $this->assertSame('ANS', TagGenerator::from('Andina Seguros'));
        $this->assertSame('GRM', TagGenerator::from('Grupo Meridiano'));
        $this->assertSame('CAV', TagGenerator::from('Cafetera del Valle'));
        $this->assertSame('EDS', TagGenerator::from('Editorial Sextante'));
    }

    public function test_it_drops_the_company_type_and_connectors(): void
    {
        // SAS says what kind of company it is, never which one.
        $this->assertSame('POL', TagGenerator::from('Portal Logístico SAS'));
        $this->assertSame('CLN', TagGenerator::from('Clínica del Norte'));
        $this->assertSame('TRA', TagGenerator::from('Transportes Aurora SAS'));
    }

    public function test_three_or_more_words_become_their_initials(): void
    {
        $this->assertSame('ABC', TagGenerator::from('Alfa Beta Carga'));
        $this->assertSame('ABC', TagGenerator::from('Alfa Beta Carga Delta'));
    }

    public function test_a_single_word_becomes_its_first_three_letters(): void
    {
        $this->assertSame('MER', TagGenerator::from('Mercadeo'));
        $this->assertSame('ECO', TagGenerator::from('Ecopetrol'));
    }

    public function test_accents_are_stripped_rather_than_dropped(): void
    {
        $this->assertSame('MED', TagGenerator::from('Médica'));
        $this->assertSame('NUN', TagGenerator::from('Ñuñoa'));
    }

    public function test_a_short_name_is_padded_to_three_characters(): void
    {
        $this->assertSame('AXX', TagGenerator::from('A'));
        $this->assertSame('ABX', TagGenerator::from('AB'));
    }

    public function test_a_name_made_only_of_noise_still_produces_a_tag(): void
    {
        $tag = TagGenerator::from('SAS LTDA');

        $this->assertMatchesRegularExpression('/^[A-Z0-9]{3}$/', $tag);
    }

    public function test_every_tag_matches_the_database_constraint(): void
    {
        $names = ['Andina Seguros', 'Ñuñoa', 'A', '3M Colombia', 'Café & Co', 'SAS LTDA'];

        foreach ($names as $name) {
            $this->assertMatchesRegularExpression(
                '/^[A-Z0-9]{3}$/',
                TagGenerator::from($name),
                "El tag de «{$name}» debe cumplir el check de la base."
            );
        }
    }

    public function test_an_unused_tag_is_returned_as_is(): void
    {
        $tag = TagGenerator::unique('Andina Seguros', fn (): bool => false);

        $this->assertSame('ANS', $tag);
    }

    public function test_a_collision_is_disambiguated_without_losing_the_stem(): void
    {
        $taken = ['TRA'];

        $tag = TagGenerator::unique('Transportes Aurora SAS', fn (string $candidate): bool => in_array($candidate, $taken, true));

        $this->assertSame('TR2', $tag, 'La desambiguacion conserva las dos primeras letras.');
    }

    public function test_it_keeps_trying_until_it_finds_a_free_tag(): void
    {
        $taken = ['TRA', 'TR2', 'TR3'];

        $tag = TagGenerator::unique('Transportes Aurora SAS', fn (string $candidate): bool => in_array($candidate, $taken, true));

        $this->assertSame('TR4', $tag);
    }

    public function test_it_fails_loudly_when_every_candidate_is_taken(): void
    {
        $this->expectException(RuntimeException::class);

        TagGenerator::unique('Transportes Aurora', fn (): bool => true);
    }
}
