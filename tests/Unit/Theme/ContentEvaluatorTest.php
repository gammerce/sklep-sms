<?php

namespace Tests\Unit\Theme;

use App\Theme\ContentEvaluator;
use Tests\Psr4\TestCases\TestCase;

class ExampleUrl
{
    public function getValue(): string
    {
        return "123";
    }
}

class ContentEvaluatorTest extends TestCase
{
    /**
     * @test
     * @dataProvider evaluateTextDataProvider
     */
    public function evaluate_text(string $text, array $data, string $expectedOutput)
    {
        // given
        $evaluator = new ContentEvaluator();

        // when
        $output = $evaluator->evaluate($text, $data);

        // then
        $this->assertSame($expectedOutput, $output);
    }

    public function evaluateTextDataProvider(): array
    {
        return [
            [
                'test {{ $url }} blah',
                ["url" => "https://example.com"],
                "test https://example.com blah",
            ],
            [
                'test {{ $url }} blah {{ $crap }}',
                ["url" => "https://example.com", "crap" => "foo"],
                "test https://example.com blah foo",
            ],
            [
                'test {{ $url }} blah',
                ["url" => "<a href=''>Text</a>"],
                "test &lt;a href=''&gt;Text&lt;/a&gt; blah",
            ],
            [
                'test {!! $url !!} blah',
                ["url" => "<a href=''>Text</a>"],
                "test <a href=''>Text</a> blah",
            ],
            ['test {{ $url->getValue() }} blah', ["url" => new ExampleUrl()], "test 123 blah"],
            ['test {{ __("key") }} blah', ["url" => new ExampleUrl()], "test key blah"],
            [
                '{!!
                        $serversLink !!}',
                ["serversLink" => "links"],
                "links",
            ],
            ['{{ constant("PHP_VERSION") }}', [], "[#ERROR_SYNTAX]"],
            ['<html lang="{{ $lang }}"></html>', ["lang" => "pl"], '<html lang="pl"></html>'],
            [
                '<meta name="viewport" content="width=device-width,initial-scale=1" />',
                [],
                '<meta name="viewport" content="width=device-width,initial-scale=1" />',
            ],
        ];
    }
}
