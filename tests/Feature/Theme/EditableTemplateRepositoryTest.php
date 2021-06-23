<?php
namespace Tests\Feature\Theme;

use App\Theme\EditableTemplateRepository;
use Tests\Psr4\TestCases\TestCase;

class EditableTemplateRepositoryTest extends TestCase
{
    private EditableTemplateRepository $editableTemplateRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->editableTemplateRepository = $this->app->make(EditableTemplateRepository::class);
    }

    /** @test */
    public function list_editable_templates()
    {
        // given
        $this->factory->template(["theme" => "foo"]);

        // when
        $templates = $this->editableTemplateRepository->list();

        // then
        $this->assertEquals(
            [
                "shop/pages/contact",
                "shop/pages/regulations",
                "shop/services/goresnick_desc",
                "shop/services/goresslot_desc",
                "shop/services/govip_desc",
                "shop/services/govippro_desc",
                "shop/services/resnick_desc",
                "shop/services/resslot_desc",
                "shop/services/vip_desc",
                "shop/services/vippro_desc",
                "shop/styles/general",
            ],
            $templates
        );
    }

    /** @test */
    public function check_if_template_is_editable()
    {
        // when
        $result1 = $this->editableTemplateRepository->isEditable("shop/pages/contact");
        $result2 = $this->editableTemplateRepository->isEditable("shop/pages/blah");

        // then
        $this->assertTrue($result1);
        $this->assertFalse($result2);
    }
}
