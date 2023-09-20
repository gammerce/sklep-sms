<?php

namespace Tests\Unit\Http\Validation;

use App\Exceptions\ValidationException;
use App\Http\Validation\Rules\FullNameRule;
use App\Http\Validation\Rules\PostalCodeRule;
use Tests\Psr4\TestCases\UnitTestCase;

class FullNameRuleTest extends UnitTestCase
{
    public function validFullNames(): array
    {
        return [["John Wick"], ["John Johny"], ["Michał Nowak"], ["Zażółć GęśląJaźń"]];
    }

    /**
     * @test
     * @dataProvider validFullNames
     */
    public function passes_validation(string $fullName)
    {
        // given
        $postalCodeRule = new FullNameRule();

        // when
        $postalCodeRule->validate("", $fullName, []);
    }

    public function invalidFullNames(): array
    {
        return [["xyz"], ["to jestem ja"], [""], [null]];
    }

    /**
     * @test
     * @dataProvider invalidFullNames
     */
    public function fails_validation(?string $fullName)
    {
        // given
        $this->expectException(ValidationException::class);

        $postalCodeRule = new FullNameRule();

        // when
        $postalCodeRule->validate("", $fullName, []);
    }
}
