<?php
namespace Tests\Feature\Repositories;

use App\Repositories\TemplateRepository;
use Tests\Psr4\TestCases\TestCase;

class TemplateRepositoryTest extends TestCase
{
    private TemplateRepository $templateRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->templateRepository = $this->app->make(TemplateRepository::class);
    }

    /** @test */
    public function create_template()
    {
        // when
        $theme = "example";
        $name = "foobar";
        $content = "a b c";
        $template = $this->templateRepository->create($theme, $name, $content);

        // then
        $this->assertEquals($template->getTheme(), $theme);
        $this->assertEquals($template->getName(), $name);
        $this->assertEquals($template->getContent(), $content);
    }

    /** @test */
    public function get_template()
    {
        // given
        $template = $this->factory->template();

        // when
        $fetchedTemplate = $this->templateRepository->get($template->getId());

        // then
        $this->assertEquals($template->getId(), $fetchedTemplate->getId());
        $this->assertEquals($template->getTheme(), $fetchedTemplate->getTheme());
        $this->assertEquals($template->getName(), $fetchedTemplate->getName());
        $this->assertEquals($template->getContent(), $fetchedTemplate->getContent());
        $this->assertEquals($template->getCreatedAt(), $fetchedTemplate->getCreatedAt());
        $this->assertEquals($template->getUpdatedAt(), $fetchedTemplate->getUpdatedAt());
    }

    /** @test */
    public function update_template()
    {
        // given
        $template = $this->factory->template([
            "content" => "baz",
        ]);

        // when
        $this->templateRepository->update($template->getId(), "foobar");

        // then
        $freshTemplate = $this->templateRepository->get($template->getId());
        $this->assertEquals("foobar", $freshTemplate->getContent());
    }

    /** @test */
    public function delete_template()
    {
        // given
        $template = $this->factory->template();

        // when
        $this->templateRepository->delete($template->getId());

        // then
        $freshTemplate = $this->templateRepository->get($template->getId());
        $this->assertNull($freshTemplate);
    }

    /** @test */
    public function find_by_theme_and_name()
    {
        // given
        $template = $this->factory->template([
            "theme" => "foo",
            "name" => "bar",
        ]);

        // when
        $foundTemplate = $this->templateRepository->find("foo", "bar");

        // then
        $this->assertNotNull($foundTemplate);
        $this->assertEquals($template->getId(), $foundTemplate->getId());
        $this->assertEquals("foo", $foundTemplate->getTheme());
        $this->assertEquals("bar", $foundTemplate->getName());
    }

    /** @test */
    public function cannot_find_using_case_insensitive_name()
    {
        // given
        $this->factory->template([
            "theme" => "foo",
            "name" => "bAr",
        ]);

        // when
        $foundTemplate = $this->templateRepository->find("foo", "bar");

        // then
        $this->assertNull($foundTemplate);
    }

    /** @test */
    public function cannot_find_using_case_insensitive_theme()
    {
        // given
        $this->factory->template([
            "theme" => "fOo",
            "name" => "bar",
        ]);

        // when
        $foundTemplate = $this->templateRepository->find("foo", "bar");

        // then
        $this->assertNull($foundTemplate);
    }
}
