<?php
namespace App\User;

use MyCLabs\Enum\Enum;

/**
 * @method static Permission ACP()
 * @method static Permission GROUPS_MANAGEMENT()
 * @method static Permission GROUPS_VIEW()
 * @method static Permission INCOME_VIEW()
 * @method static Permission LOGS_MANAGEMENT()
 * @method static Permission LOGS_VIEW()
 * @method static Permission PLAYER_FLAGS_VIEW()
 * @method static Permission PRICING_MANAGEMENT()
 * @method static Permission PRICING_VIEW()
 * @method static Permission PROMO_CODES_MANAGEMENT()
 * @method static Permission PROMO_CODES_VIEW()
 * @method static Permission SERVERS_MANAGEMENT()
 * @method static Permission SERVERS_VIEW()
 * @method static Permission SERVICES_MANAGEMENT()
 * @method static Permission SERVICES_VIEW()
 * @method static Permission SETTINGS_MANAGEMENT()
 * @method static Permission SMS_CODES_MANAGEMENT()
 * @method static Permission SMS_CODES_VIEW()
 * @method static Permission UPDATE()
 * @method static Permission USERS_MANAGEMENT()
 * @method static Permission USERS_VIEW()
 * @method static Permission USER_SERVICES_MANAGEMENT()
 * @method static Permission USER_SERVICES_VIEW()
 */
final class Permission extends Enum
{
    const ACP = "acp";
    const GROUPS_MANAGEMENT = "manage_groups";
    const GROUPS_VIEW = "view_groups";
    const INCOME_VIEW = "view_income";
    const LOGS_MANAGEMENT = "manage_logs";
    const LOGS_VIEW = "view_logs";
    const PLAYER_FLAGS_VIEW = "view_player_flags";
    const PRICING_MANAGEMENT = "manage_pricing";
    const PRICING_VIEW = "view_pricing";
    const PROMO_CODES_MANAGEMENT = "manage_promo_codes";
    const PROMO_CODES_VIEW = "view_promo_codes";
    const SERVERS_MANAGEMENT = "manage_servers";
    const SERVERS_VIEW = "view_servers";
    const SERVICES_MANAGEMENT = "manage_services";
    const SERVICES_VIEW = "view_services";
    const SETTINGS_MANAGEMENT = "manage_settings";
    const SMS_CODES_MANAGEMENT = "manage_sms_codes";
    const SMS_CODES_VIEW = "view_sms_codes";
    const UPDATE = "update";
    const USERS_MANAGEMENT = "manage_users";
    const USERS_VIEW = "view_users";
    const USER_SERVICES_MANAGEMENT = "manage_user_services";
    const USER_SERVICES_VIEW = "view_user_services";
}
