<?php

namespace Tests\Unit\Clientes;

use App\Support\Clientes\NitVerificationDigit;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * A NIT whose verification digit is wrong is an invoice the DIAN rejects. The
 * reference cases are real Colombian NITs, because a test written against the
 * implementation would only prove the implementation agrees with itself.
 */
class NitVerificationDigitTest extends TestCase
{
    /**
     * @return array<string, array{string, int}>
     */
    public static function realNits(): array
    {
        return [
            'Bancolombia' => ['890903938', 8],
            'Ecopetrol' => ['899999068', 1],
            'Grupo Éxito' => ['890900608', 9],
            'Avianca' => ['890100577', 6],
        ];
    }

    #[DataProvider('realNits')]
    public function test_it_computes_the_digit_of_a_real_nit(string $nit, int $expected): void
    {
        $this->assertSame($expected, NitVerificationDigit::for($nit));
    }

    public function test_formatting_does_not_change_the_result(): void
    {
        $this->assertSame(8, NitVerificationDigit::for('890.903.938'));
        $this->assertSame(8, NitVerificationDigit::for('890 903 938'));
        $this->assertSame(8, NitVerificationDigit::for('  890903938  '));
    }

    public function test_a_digit_already_written_after_the_dash_is_ignored(): void
    {
        // Otherwise the claimed digit would take part in computing itself.
        $this->assertSame(8, NitVerificationDigit::for('890.903.938-8'));
        $this->assertSame(8, NitVerificationDigit::for('890.903.938-0'));
    }

    public function test_it_verifies_a_claimed_digit(): void
    {
        $this->assertTrue(NitVerificationDigit::matches('890903938', 8));
        $this->assertTrue(NitVerificationDigit::matches('890.903.938', '8'));

        $this->assertFalse(NitVerificationDigit::matches('890903938', 7));
        $this->assertFalse(NitVerificationDigit::matches('890903938', null));
    }

    public function test_an_empty_or_oversized_document_has_no_digit(): void
    {
        $this->assertNull(NitVerificationDigit::for(''));
        $this->assertNull(NitVerificationDigit::for('abc'));
        $this->assertNull(NitVerificationDigit::for(str_repeat('9', 16)));
    }

    /**
     * The prototype's sample data would not pass this validation, which is the
     * point: 830.114.921 verifies to 1, not to the 7 it ships with.
     */
    public function test_the_prototypes_sample_nit_does_not_verify(): void
    {
        $this->assertSame(1, NitVerificationDigit::for('830114921'));
        $this->assertFalse(NitVerificationDigit::matches('830114921', 7));
    }
}
