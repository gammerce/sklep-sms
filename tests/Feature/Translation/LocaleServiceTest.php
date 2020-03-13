<?php
namespace Tests\Feature\Translation;

use App\Translation\LocaleService;
use Symfony\Component\HttpFoundation\Request;
use Tests\Psr4\Concerns\RequesterConcern;
use Tests\Psr4\TestCases\TestCase;

class LocaleServiceTest extends TestCase
{
    use RequesterConcern;

    protected $mockLocale = false;

    /** @test */
    public function defaults_to_polish()
    {
        // given
        $request = Request::create('');
        $this->app->instance(Request::class, $request);

        /** @var LocaleService $localeService */
        $localeService = $this->app->make(LocaleService::class);

        // when
        $locale = $localeService->getLocale($request);

        // then
        $this->assertEquals('polish', $locale);
    }

    /** @test */
    public function resolves_locale_from_ip()
    {
        // given
        $request = Request::create(
            '',
            '',
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => '8.8.8.8',
            ]
        );
        $this->app->instance(Request::class, $request);

        /** @var LocaleService $localeService */
        $localeService = $this->app->make(LocaleService::class);

        // when
        $locale = $localeService->getLocale($request);

        // then
        $this->assertEquals('english', $locale);
    }

    /** @test */
    public function resolves_locale_from_cookie()
    {
        // given
        $request = Request::create('', '', [], ['language' => 'english']);
        $this->app->instance(Request::class, $request);

        /** @var LocaleService $localeService */
        $localeService = $this->app->make(LocaleService::class);

        // when
        $locale = $localeService->getLocale($request);

        // then
        $this->assertEquals('english', $locale);
    }

    /** @test */
    public function resolves_locale_from_query()
    {
        // given
        $request = Request::create('', '', ['language' => 'english']);
        $this->app->instance(Request::class, $request);

        /** @var LocaleService $localeService */
        $localeService = $this->app->make(LocaleService::class);

        // when
        $locale = $localeService->getLocale($request);

        // then
        $this->assertEquals('english', $locale);
    }

    /** @test */
    public function fallbacks_to_polish_if_requester_fails()
    {
        // given
        $this->mockRequester();
        $this->requesterMock->shouldReceive('get')->andReturnNull();
        $request = Request::create(
            '',
            '',
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => '8.8.8.8',
            ]
        );
        $this->app->instance(Request::class, $request);

        /** @var LocaleService $localeService */
        $localeService = $this->app->make(LocaleService::class);

        // when
        $locale = $localeService->getLocale($request);

        // then
        $this->assertEquals('polish', $locale);
    }
}
