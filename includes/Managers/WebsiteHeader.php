<?php
namespace App\Managers;

class WebsiteHeader
{
    /** @var string[] */
    private array $scripts = [];

    /**
     * Add JS script
     *
     * @param string $path
     */
    public function addScript($path): void
    {
        if (!in_array($path, $this->scripts)) {
            $this->scripts[] = $path;
        }
    }

    public function getScripts(): string
    {
        return collect($this->scripts)
            ->map(fn($path) => "<script type=\"text/javascript\" src=\"{$path}\"></script>")
            ->join("\n");
    }
}
