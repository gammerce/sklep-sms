<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\Rule;
use App\Support\Database;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class UniqueUsernameRule implements Rule
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
        $warnings = check_for_warnings("username", $value);

        $statement = $this->db->statement(
            "SELECT `uid` FROM `ss_users` WHERE `username` = ? AND `uid` != ?"
        );
        $statement->execute([$value, $this->exceptUserId]);

        if ($statement->rowCount()) {
            $warnings[] = $this->lang->t('nick_occupied');
        }

        return $warnings;
    }
}
