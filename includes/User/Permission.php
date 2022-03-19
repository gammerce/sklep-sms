<?php
namespace App\User;

use MyCLabs\Enum\Enum;

/**
 * @method static Permission ACP()
 * @method static Permission MANAGE_GROUPS()
 * @method static Permission MANAGE_LOGS()
 * @method static Permission MANAGE_PROMO_CODES()
 * @method static Permission MANAGE_PRICING()
 * @method static Permission MANAGE_SERVERS()
 * @method static Permission MANAGE_SERVICES()
 * @method static Permission MANAGE_SETTINGS()
 * @method static Permission MANAGE_SMS_CODES()
 * @method static Permission MANAGE_USERS()
 * @method static Permission MANAGE_USER_SERVICES()
 * @method static Permission UPDATE()
 * @method static Permission VIEW_GROUPS()
 * @method static Permission VIEW_INCOME()
 * @method static Permission VIEW_LOGS()
 * @method static Permission VIEW_PLAYER_FLAGS()
 * @method static Permission VIEW_PRICING()
 * @method static Permission VIEW_PROMO_CODES()
 * @method static Permission VIEW_SERVERS()
 * @method static Permission VIEW_SERVICES()
 * @method static Permission VIEW_SMS_CODES()
 * @method static Permission VIEW_USERS()
 * @method static Permission VIEW_USER_SERVICES()
 */
final class Permission extends Enum
{
    const ACP = "acp";
    const MANAGE_GROUPS = "manage_groups";
    const MANAGE_LOGS = "manage_logs";
    const MANAGE_PROMO_CODES = "manage_promo_codes";
    const MANAGE_PRICING = "manage_pricing";
    const MANAGE_SERVERS = "manage_servers";
    const MANAGE_SERVICES = "manage_services";
    const MANAGE_SETTINGS = "manage_settings";
    const MANAGE_SMS_CODES = "manage_sms_codes";
    const MANAGE_USERS = "manage_users";
    const MANAGE_USER_SERVICES = "manage_user_services";
    const UPDATE = "update";
    const VIEW_GROUPS = "view_groups";
    const VIEW_INCOME = "view_income";
    const VIEW_LOGS = "view_logs";
    const VIEW_PLAYER_FLAGS = "view_player_flags";
    const VIEW_PRICING = "view_pricing";
    const VIEW_PROMO_CODES = "view_promo_codes";
    const VIEW_SERVERS = "view_servers";
    const VIEW_SERVICES = "view_services";
    const VIEW_SMS_CODES = "view_sms_codes";
    const VIEW_USERS = "view_users";
    const VIEW_USER_SERVICES = "view_user_services";
}
