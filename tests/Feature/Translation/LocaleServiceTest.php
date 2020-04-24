<?php
namespace Tests\Feature\Translation;

use App\Translation\LocaleService;
use Symfony\Component\HttpFoundation\Request;
use Tests\Psr4\TestCases\TestCase;

class LocaleServiceTest extends TestCase
{
    protected $mockLocale = false;

    /** @test */
    public function defaults_to_polish()
    {
        // given
        $request = Request::create("", "GET", [], [], [], ["HTTP_ACCEPT_LANGUAGE" => ""]);
        $this->app->instance(Request::class, $request);

        /** @var LocaleService $localeService */
        $localeService = $this->app->make(LocaleService::class);

        // when
        $locale = $localeService->getLocale($request);

        // then
        $this->assertEquals("polish", $locale);
    }

    /** @test */
    public function resolves_locale_from_header()
    {
        // given
        $request = Request::create(
            "",
            "",
            [],
            [],
            [],
            [
                "HTTP_ACCEPT_LANGUAGE" => "en-us,en;q=0.5",
            ]
        );
        $this->app->instance(Request::class, $request);

        /** @var LocaleService $localeService */
        $localeService = $this->app->make(LocaleService::class);

        // when
        $locale = $localeService->getLocale($request);

        // then
        $this->assertEquals("english", $locale);
    }

    /** @test */
    public function resolves_locale_from_cookie()
    {
        // given
        $request = Request::create(
            "",
            "GET",
            [],
            ["language" => "english"],
            [],
            ["HTTP_ACCEPT_LANGUAGE" => ""]
        );
        $this->app->instance(Request::class, $request);

        /** @var LocaleService $localeService */
        $localeService = $this->app->make(LocaleService::class);

        // when
        $locale = $localeService->getLocale($request);

        // then
        $this->assertEquals("english", $locale);
    }

    /** @test */
    public function resolves_locale_from_query()
    {
        // given
        $request = Request::create(
            "",
            "GET",
            ["language" => "english"],
            [],
            [],
            ["HTTP_ACCEPT_LANGUAGE" => ""]
        );
        $this->app->instance(Request::class, $request);

        /** @var LocaleService $localeService */
        $localeService = $this->app->make(LocaleService::class);

        // when
        $locale = $localeService->getLocale($request);

        // then
        $this->assertEquals("english", $locale);
    }

    /** @test */
    public function resolves_locale_from_short_locale_in_query()
    {
        // given
        $request = Request::create(
            "",
            "GET",
            ["language" => "en"],
            [],
            [],
            ["HTTP_ACCEPT_LANGUAGE" => ""]
        );
        $this->app->instance(Request::class, $request);

        /** @var LocaleService $localeService */
        $localeService = $this->app->make(LocaleService::class);

        // when
        $locale = $localeService->getLocale($request);

        // then
        $this->assertEquals("english", $locale);
    }
}
