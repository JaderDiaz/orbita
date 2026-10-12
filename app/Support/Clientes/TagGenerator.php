<?php

namespace App\Support\Clientes;

use RuntimeException;

/**
 * Derives a client's three-character numbering tag from its name.
 *
 * The algorithm is the prototype's `genTag()`, which the agency is used to:
 * strip accents, drop the corporate suffixes and connectors that carry no
 * meaning, then take initials.
 *
 * What the prototype does NOT do is resolve collisions, and the tag is the
 * prefix of this client's invoice and quote series. Two clients starting
 * "Transportes" would both get TRA and the install could issue FAC-TRA-0001
 * twice, which is a fiscal problem rather than a cosmetic one. `unique()`
 * disambiguates, and a unique index guarantees the rest.
 */
class TagGenerator
{
    public const LENGTH = 3;

    /**
     * Words that identify a company type or join two others, so they say
     * nothing about which company this is.
     *
     * @var list<string>
     */
    private const NOISE = [
        'SAS', 'S.A.S', 'S.A.S.', 'SA', 'S.A.', 'LTDA', 'LTDA.',
        'DE', 'DEL', 'LA', 'LAS', 'LOS', 'EL', 'Y', 'E', '&',
    ];

    /**
     * Characters appended when the derived tag is already taken. Digits first
     * because they read as "the second Transportes", then letters.
     */
    private const DISAMBIGUATORS = '23456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * The tag a name derives from, before any collision check.
     */
    public static function from(string $name): string
    {
        $words = self::meaningfulWords($name);

        $tag = match (true) {
            $words === [] => '',
            count($words) === 1 => substr($words[0], 0, self::LENGTH),
            count($words) === 2 => substr($words[0], 0, 2).substr($words[1], 0, 1),
            default => implode('', array_map(
                fn (string $word): string => substr($word, 0, 1),
                array_slice($words, 0, self::LENGTH),
            )),
        };

        return str_pad(substr($tag, 0, self::LENGTH), self::LENGTH, 'X');
    }

    /**
     * A tag nothing else is using.
     *
     * The caller supplies the check, so this stays testable without a database
     * and the query lives where the query belongs.
     *
     * @param  callable(string): bool  $isTaken
     */
    public static function unique(string $name, callable $isTaken): string
    {
        $base = self::from($name);

        if (! $isTaken($base)) {
            return $base;
        }

        $stem = substr($base, 0, self::LENGTH - 1);

        foreach (str_split(self::DISAMBIGUATORS) as $suffix) {
            $candidate = $stem.$suffix;

            if (! $isTaken($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException("No hay un tag disponible derivado de [{$name}].");
    }

    /**
     * @return list<string>
     */
    private static function meaningfulWords(string $name): array
    {
        $normalised = strtoupper(self::stripAccents($name));
        $normalised = preg_replace('/[^A-Z0-9&.\s]/', ' ', $normalised) ?? '';

        $words = preg_split('/\s+/', trim($normalised)) ?: [];

        $meaningful = array_values(array_filter(
            $words,
            fn (string $word): bool => $word !== '' && ! in_array($word, self::NOISE, true),
        ));

        // A name made only of noise still has to produce something, so fall
        // back to the original words rather than an empty tag.
        $source = $meaningful !== [] ? $meaningful : array_values(array_filter($words, fn (string $word): bool => $word !== ''));

        return array_map(
            fn (string $word): string => preg_replace('/[^A-Z0-9]/', '', $word) ?? '',
            $source,
        );
    }

    private static function stripAccents(string $value): string
    {
        return strtr($value, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
        ]);
    }
}
