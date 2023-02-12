<?php
namespace App\Support;

class Meta
{
    private MetaParser $metaParser;
    private BasePath $path;
    private array $meta;

    public function __construct(MetaParser $metaParser, BasePath $path)
    {
        $this->metaParser = $metaParser;
        $this->path = $path;
    }

    public function load(): void
    {
        $path = $this->path->to("confidential/.meta");
        $this->meta = $this->metaParser->parse($path);
    }

    public function getVersion(): string
    {
        return $this->get("VERSION", "unknown");
    }

    public function getBuild(): string
    {
        return $this->get("BUILD", "unknown");
    }

    /**
     * @param string $key
     * @param string|null $default
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        return array_get($this->meta, $key, $default);
    }
}
