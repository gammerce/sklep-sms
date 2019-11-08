<?php
namespace Tests\Psr4\Concerns;

use App\System\Path;

trait FixtureConcern
{
    /**
     * @param string $name
     * @return string
     */
    protected function loadFixture($name)
    {
        /** @var Path $pathBuilder */
        $pathBuilder = $this->app->make(Path::class);
        $path = $pathBuilder->to("tests/Fixtures/$name.json");

        return file_get_contents($path);
    }
}
