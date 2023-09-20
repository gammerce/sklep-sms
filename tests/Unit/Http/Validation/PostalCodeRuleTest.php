<?php

namespace Tests\Unit\Http\Validation;

use App\Exceptions\ValidationException;
use App\Http\Validation\Rules\PostalCodeRule;
use Tests\Psr4\TestCases\UnitTestCase;

class PostalCodeRuleTest extends UnitTestCase
{
    public function validPostalCodes(): array
    {
        return [["00-000"], ["01-960"]];
    }

    /**
     * @test
     * @dataProvider validPostalCodes
     */
    public function passes_validation(string $postalCode)
    {
        // given
        $postalCodeRule = new PostalCodeRule();

        // when
        $postalCodeRule->validate("", $postalCode, []);
    }

    public function invalidPostalCodes(): array
    {
        return [["abc"], ["0000"], [""], [null]];
    }

    /**
     * @test
     * @dataProvider invalidPostalCodes
     */
    public function fails_validation(?string $postalCode)
    {
        // given
        $this->expectException(ValidationException::class);

        $postalCodeRule = new PostalCodeRule();

        // when
        $postalCodeRule->validate("", $postalCode, []);
    }
}
