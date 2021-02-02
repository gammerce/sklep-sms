<?php
namespace Tests\Unit\Install;

use App\Install\EnvCreator;
use App\Support\FileSystemContract;
use App\Support\Path;
use Tests\Psr4\MemoryFileSystem;
use Tests\Psr4\TestCases\TestCase;

class EnvCreatorTest extends TestCase
{
    private FileSystemContract $fileSystem;
    private EnvCreator $envCreator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fileSystem = new MemoryFileSystem();
        $this->envCreator = new EnvCreator(new Path("/test"), $this->fileSystem);
    }

    /** @test */
    public function creates_env_file()
    {
        // given

        // when
        $this->envCreator->create("example", "80", "my_example", "my_user", "my_password");

        // then
        $envContent = $this->fileSystem->get("/test/confidential/.env");
        $this->assertSame(
            <<<EOF
DB_HOST=example
DB_PORT=80
DB_DATABASE=my_example
DB_USERNAME=my_user
DB_PASSWORD=my_password

MAIL_HOST=
MAIL_PASSWORD=
EOF
            ,
            $envContent
        );
    }
}
