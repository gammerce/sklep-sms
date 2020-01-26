<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\Rule;
use App\System\Database;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class UniqueUserEmailRule implements Rule
{
    /** @var Database */
    private $db;

    /** @var Translator */
    private $lang;

    /** @var int */
    private $exceptUserId;

    public function __construct($exceptUserId = null)
    {
        $this->db = app()->make(Database::class);
        $translationManager = app()->make(TranslationManager::class);
        $this->lang = $translationManager->user();
        $this->exceptUserId = $exceptUserId;
    }

    public function validate($attribute, $value, array $data)
    {
        if (!strlen($value)) {
            return [];
        }

        $statement = $this->db->statement(
            "SELECT `uid` FROM `ss_users` WHERE `email` = ? AND `uid` != ?"
        );
        $statement->execute([$value, $this->exceptUserId]);

        if ($statement->rowCount()) {
            return [$this->lang->t('email_occupied')];
        }

        return [];
    }
}
