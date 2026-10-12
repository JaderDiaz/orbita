<?php

namespace App\Support\Clientes;

/**
 * The DIAN verification digit of a NIT.
 *
 * The prototype accepts any text in the document field. A NIT whose check digit
 * is wrong is an invoice the DIAN rejects, and the arithmetic is modulo 11 over
 * a fixed prime series — cheap to compute and cheap to verify.
 */
class NitVerificationDigit
{
    /**
     * The DIAN weights, applied right to left over the NIT's digits.
     *
     * @var list<int>
     */
    private const WEIGHTS = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71];

    /**
     * Compute the digit for a NIT, ignoring dots, spaces and any digit already
     * written after the dash.
     */
    public static function for(string $nit): ?int
    {
        $digits = self::digitsOf($nit);

        if ($digits === '' || strlen($digits) > count(self::WEIGHTS)) {
            return null;
        }

        $sum = 0;

        foreach (array_reverse(str_split($digits)) as $index => $digit) {
            $sum += (int) $digit * self::WEIGHTS[$index];
        }

        $remainder = $sum % 11;

        return $remainder < 2 ? $remainder : 11 - $remainder;
    }

    public static function matches(string $nit, int|string|null $claimed): bool
    {
        $expected = self::for($nit);

        return $expected !== null && $claimed !== null && (int) $claimed === $expected;
    }

    /**
     * Only the digits of the NIT itself. Anything after a dash is the claimed
     * verification digit and must not take part in computing it.
     */
    private static function digitsOf(string $nit): string
    {
        $withoutClaimed = explode('-', trim($nit))[0];

        return preg_replace('/\D/', '', $withoutClaimed) ?? '';
    }
}
