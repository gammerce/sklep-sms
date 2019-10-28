<?php
namespace App\Validation\Rules;

use App\Database;
use App\TranslationManager;
use App\Translator;
use App\Validation\Rule;

class AntispamQuestionRule implements Rule
{
    /** @var Database */
    private $db;

    /** @var Translator */
    private $lang;

    public function __construct(Database $db, TranslationManager $translationManager)
    {
        $this->db = $db;
        $this->lang = $translationManager->user();
    }

    public function validate($attribute, $value, array $data)
    {
        $asId = $data["as_id"];
        $asAnswer = $data["as_answer"];

        $result = $this->db->query(
            $this->db->prepare(
                "SELECT * FROM `" . TABLE_PREFIX . "antispam_questions` " . "WHERE `id` = '%d'",
                [$asId]
            )
        );

        $antispamQuestion = $this->db->fetchArrayAssoc($result);

        if (!in_array(strtolower($asAnswer), explode(";", $antispamQuestion['answers']))) {
            return [$this->lang->translate('wrong_anti_answer')];
        }

        return [];
    }
}