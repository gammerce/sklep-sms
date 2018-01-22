<?php
namespace App\Exceptions;

use Exception;

class SqlQueryException extends Exception
{
    /** @var  string */
    private $query;

    /** @var  string */
    private $message_id;

    /** @var  string */
    private $error;

    /** @var  int */
    private $errorno;

    /**
     * @param bool $escape
     *
     * @return string
     */
    public function getQuery($escape = true)
    {
        return $escape ? htmlspecialchars($this->query) : $this->query;
    }

    /**
     * @return string
     */
    public function getMessageId()
    {
        return $this->message_id;
    }

    /**
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param string $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * @param string $error
     */
    public function setError($error)
    {
        $this->error = $error;
    }

    /**
     * @return int
     */
    public function getErrorno()
    {
        return $this->errorno;
    }

    /**
     * @param int $errorno
     */
    public function setErrorno($errorno)
    {
        $this->errorno = $errorno;
    }
}
