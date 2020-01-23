<?php
namespace App\Models;

class AntispamQuestion
{
    /** @var int */
    private $id;

    /** @var string */
    private $question;

    /** @var string */
    private $answers;

    public function __construct($id, $question, $answers)
    {
        $this->id = $id;
        $this->question = $question;
        $this->answers = $answers;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getQuestion()
    {
        return $this->question;
    }

    /**
     * @return string
     */
    public function getAnswers()
    {
        return $this->answers;
    }
}
