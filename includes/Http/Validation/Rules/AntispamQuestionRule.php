<?php
namespace App\Http\Validation\Rules;

use App\System\Database;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\Http\Validation\Rule;

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
