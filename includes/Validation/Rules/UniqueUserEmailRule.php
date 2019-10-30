<?php
namespace App\Validation\Rules;

use App\Database;
use App\TranslationManager;
use App\Translator;
use App\Validation\Rule;

class UniqueUserEmailRule implements Rule
{
    /** @var Database */
    private $db;

    /** @var Translator */
    private $lang;

    /** @var int */
    private $userId = 0;

    public function __construct(Database $db, TranslationManager $translationManager)
    {
        $this->db = $db;
        $this->lang = $translationManager->user();
    }

    public function validate($attribute, $value, array $data)
    {
        if (!strlen($value)) {
            return [];
        }

        $result = $this->db->query(
            $this->db->prepare(
                "SELECT `uid` FROM `" .
                    TABLE_PREFIX .
                    "users` WHERE `email` = '%s' AND `uid` != '%d'",
                [$value, $this->userId]
            )
        );

        if ($this->db->numRows($result)) {
            return [$this->lang->translate('email_occupied')];
        }

        return [];
    }

    /**
     * @param int $userId
     * @return $this
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }
}
