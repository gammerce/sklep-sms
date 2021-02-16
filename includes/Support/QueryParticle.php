<?php
namespace App\Support;

class QueryParticle
{
    /** @var string[] */
    private array $particles = [];
    private array $params = [];

    /**
     * @param string $query
     * @param array $params
     */
    public function add($query, array $params = []): void
    {
        $this->particles[] = $query;
        $this->params = array_merge($this->params, $params);
    }

    public function extend(QueryParticle $particle): void
    {
        $this->add($particle->text(), $particle->params());
    }

    public function isEmpty(): bool
    {
        return !$this->particles;
    }

    /**
     * @param string $glue
     * @return string
     */
    public function text($glue = " "): string
    {
        return implode($glue, $this->particles);
    }

    public function params(): array
    {
        return $this->params;
    }

    public function __toString()
    {
        return $this->text();
    }
}
