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
    public function add($query, array $params = [])
    {
        $this->particles[] = $query;
        $this->params = array_merge($this->params, $params);
    }

    public function extend(QueryParticle $particle)
    {
        $this->add($particle->text(), $particle->params());
    }

    public function isEmpty()
    {
        return !$this->particles;
    }

    /**
     * @param string $glue
     * @return string
     */
    public function text($glue = " ")
    {
        return implode($glue, $this->particles);
    }

    /**
     * @return array
     */
    public function params()
    {
        return $this->params;
    }

    public function __toString()
    {
        return $this->text();
    }
}
