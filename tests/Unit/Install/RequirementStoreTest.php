<?php
namespace Tests\Unit\Install;

use App\Install\RequirementStore;
use App\Support\Meta;
use App\Support\MetaParser;
use App\Support\BasePath;
use Tests\Psr4\MemoryFileSystem;
use Tests\Psr4\TestCases\UnitTestCase;

class RequirementStoreTest extends UnitTestCase
{
    /** @test */
    public function get_list_of_requirements()
    {
        // given
        /** @var RequirementStore $requirementStore */
        $requirementStore = $this->app->make(RequirementStore::class);

        // when
        $modules = $requirementStore->getModules();

        // then
        $this->assertEquals(
            [
                [
                    "text" => "PHP vdev lub wyżej",
                    "value" => true,
                    "required" => false,
                ],
                [
                    "text" => "Moduł CURL",
                    "value" => true,
                    "required" => true,
                ],
                [
                    "text" => "Moduł PDO",
                    "value" => true,
                    "required" => true,
                ],
            ],
            $modules
        );
    }

    /** @test */
    public function invalid_php_version()
    {
        // given
        $path = BasePath::temporary();
        $fileSystem = new MemoryFileSystem();
        $meta = new Meta(new MetaParser($fileSystem), $path);
        $requirementStore = new RequirementStore($path, $meta, $fileSystem);

        $fileSystem->put(
            $path->to("/confidential/.meta"),
            <<<EOF
BUILD=php20.0
EOF
        );
        $meta->load();

        // when
        $modules = $requirementStore->getModules();

        // then
        $this->assertEquals(
            [
                [
                    "text" => "PHP v20.0 lub wyżej",
                    "value" => false,
                    "required" => false,
                ],
                [
                    "text" => "Moduł CURL",
                    "value" => true,
                    "required" => true,
                ],
                [
                    "text" => "Moduł PDO",
                    "value" => true,
                    "required" => true,
                ],
            ],
            $modules
        );
    }
}
