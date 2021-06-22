<?php
namespace Tests\Feature\Theme;

use App\Theme\ThemeRepository;
use Tests\Psr4\TestCases\TestCase;

class ThemeRepositoryTest extends TestCase
{
    private ThemeRepository $themeRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->themeRepository = $this->app->make(ThemeRepository::class);
    }

    /** @test */
    public function list_themes()
    {
        // given
        $this->factory->template(["theme" => "foo"]);

        // when
        $themes = $this->themeRepository->list();

        // then
        $this->assertEquals(["foo", "fusion"], $themes);
    }

    /** @test */
    public function check_if_theme_exists()
    {
        // given
        $this->factory->template(["theme" => "foo"]);

        // when
        $result1 = $this->themeRepository->exists("foo");
        $result2 = $this->themeRepository->exists("bar");

        // then
        $this->assertTrue($result1);
        $this->assertFalse($result2);
    }
}
