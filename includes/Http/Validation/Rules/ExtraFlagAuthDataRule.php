<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\Http\Validation\EmptyRule;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\System\Heart;

// TODO Implement it

class ExtraFlagAuthDataRule extends BaseRule implements EmptyRule
{
    /** @var Heart */
    private $heart;

    public function __construct()
    {
        parent::__construct();
        $this->heart = app()->make(Heart::class);
    }

    public function validate($attribute, $value, array $data)
    {
        return [];
        // Typ usługi
        // Mogą być tylko 3 rodzaje typu
        if ($purchase->getOrder('type') & (ExtraFlagType::TYPE_NICK | ExtraFlagType::TYPE_IP)) {
            // Nick
            if ($purchase->getOrder('type') == ExtraFlagType::TYPE_NICK) {
                if ($warning = check_for_warnings("nick", $purchase->getOrder('auth_data'))) {
                    $warnings->add('nick', $warning);
                }

                // Sprawdzanie czy istnieje już taka usługa
                $query = $this->db->prepare(
                    "SELECT `password` FROM `" .
                        $this::USER_SERVICE_TABLE .
                        "` " .
                        "WHERE `type` = '%d' AND `auth_data` = '%s' AND `server` = '%d'",
                    [
                        ExtraFlagType::TYPE_NICK,
                        $purchase->getOrder('auth_data'),
                        isset($server) ? $server->getId() : 0,
                    ]
                );
            }
            // IP
            elseif ($purchase->getOrder('type') == ExtraFlagType::TYPE_IP) {
                if ($warning = check_for_warnings("ip", $purchase->getOrder('auth_data'))) {
                    $warnings->add('ip', $warning);
                }

                // Sprawdzanie czy istnieje już taka usługa
                $query = $this->db->prepare(
                    "SELECT `password` FROM `" .
                        $this::USER_SERVICE_TABLE .
                        "` " .
                        "WHERE `type` = '%d' AND `auth_data` = '%s' AND `server` = '%d'",
                    [
                        ExtraFlagType::TYPE_IP,
                        $purchase->getOrder('auth_data'),
                        isset($server) ? $server->getId() : 0,
                    ]
                );
            }

            // Hasło
            if ($warning = check_for_warnings("password", $purchase->getOrder('password'))) {
                $warnings->add('password', $warning);
            }
            if ($purchase->getOrder('password') != $purchase->getOrder('passwordr')) {
                $warnings->add('password_repeat', $this->lang->t('passwords_not_match'));
            }

            // Sprawdzanie czy istnieje już taka usługa
            if ($tmpPassword = $this->db->query($query)->fetchColumn()) {
                // TODO: Usunąć md5 w przyszłości
                if (
                    $tmpPassword != $purchase->getOrder('password') &&
                    $tmpPassword != md5($purchase->getOrder('password'))
                ) {
                    $warnings->add(
                        'password',
                        $this->lang->t('existing_service_has_different_password')
                    );
                }
            }

            unset($tmpPassword);
        }
        // SteamID
        elseif ($warning = check_for_warnings("sid", $purchase->getOrder('auth_data'))) {
            $warnings->add('sid', $warning);
        }
    }
}
