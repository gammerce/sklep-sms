<?php
namespace Tests\Psr4\Concerns;

use App\Support\BasePath;

trait FixtureConcern
{
    /**
     * @param string $name
     * @return string
     */
    protected function loadFixture($name): string
    {
        /** @var BasePath $pathBuilder */
        $pathBuilder = $this->app->make(BasePath::class);
        $path = $pathBuilder->to("tests/Fixtures/$name.json");

        return file_get_contents($path);
    }
}
