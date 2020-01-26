<?php
namespace App\Models;

class AntiSpamQuestion
{
    /** @var int */
    private $id;

    /** @var string */
    private $question;

    /** @var array */
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
     * @return array
     */
    public function getAnswers()
    {
        return $this->answers;
    }
}
