<?php
namespace Tests\Feature\Theme;

use App\Theme\TemplateRepository;
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
        $name = "foobar";
        $theme = "example";
        $lang = "pl";
        $content = "a b c";
        $template = $this->templateRepository->create($name, $theme, $lang, $content);

        // then
        $this->assertEquals($name, $template->getName());
        $this->assertEquals($theme, $template->getTheme());
        $this->assertEquals($lang, $template->getLang());
        $this->assertEquals($content, $template->getContent());
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
        $this->assertEquals($template->getName(), $fetchedTemplate->getName());
        $this->assertEquals($template->getTheme(), $fetchedTemplate->getTheme());
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
    public function find_by_name_theme_and_lang()
    {
        // given
        $template = $this->factory->template([
            "name" => "bar",
            "theme" => "foo",
            "lang" => "pl",
        ]);

        // when
        $foundTemplate = $this->templateRepository->find("bar", "foo", "pl");

        // then
        $this->assertNotNull($foundTemplate);
        $this->assertEquals($template->getId(), $foundTemplate->getId());
        $this->assertEquals("bar", $foundTemplate->getName());
        $this->assertEquals("foo", $foundTemplate->getTheme());
        $this->assertEquals("pl", $foundTemplate->getLang());
    }

    /** @test */
    public function cannot_find_using_case_insensitive_name()
    {
        // given
        $this->factory->template([
            "name" => "bAr",
            "theme" => "foo",
            "lang" => null,
        ]);

        // when
        $foundTemplate = $this->templateRepository->find("foo", "bar", null);

        // then
        $this->assertNull($foundTemplate);
    }

    /** @test */
    public function cannot_find_using_case_insensitive_theme()
    {
        // given
        $this->factory->template([
            "name" => "bar",
            "theme" => "fOo",
            "lang" => null,
        ]);

        // when
        $foundTemplate = $this->templateRepository->find("foo", "bar", null);

        // then
        $this->assertNull($foundTemplate);
    }

    /** @test */
    public function list_themes()
    {
        // given
        $this->factory->template(["theme" => "foo"]);
        $this->factory->template(["theme" => "bar"]);

        // when
        $themes = $this->templateRepository->listThemes();

        // then
        $this->assertEquals(["bar", "foo"], $themes);
    }

    /** @test */
    public function list_templates()
    {
        // given
        $template = $this->factory->template([
            "name" => "shop/pages/contact",
            "theme" => "foo",
            "lang" => null,
            "content" => "a b c",
        ]);

        // when
        $result = $this->templateRepository->listTemplates("foo", null);

        // then
        $this->assertCount(1, $result);
        $this->assertEquals($template->getId(), $result[0]->getId());
    }
}
