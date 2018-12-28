<?php
namespace Tests\Psr4\Concerns;

trait FixtureConcern
{
    /**
     * @param string $name
     * @return string
     */
    protected function loadFixture($name)
    {
        $path = $this->app->path("tests/Fixtures/$name.json");

        return file_get_contents($path);
    }
}
