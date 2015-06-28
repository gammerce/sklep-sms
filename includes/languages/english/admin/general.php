<?php

$lang['no_access'] = "You have no proper powers.";

$lang['license_expires'] = "License expires";

$lang['remove_install'] = "Remove install folder!";
$lang['update_available'] = "Script update available for version {1}. To download it go to <a href=\"admin.php?pid=update_web\">WWW website update</a>";
$lang['update_available_servers'] = "Update available for {1}/{2} servers. To download it go to <a href=\"admin.php?pid=update_servers\">Servers update</a>";
$lang['license_error'] = "License has expired or is incorrect. Go to <a href=\"admin.php?pid=settings\">settings</a>, in order to supply correct license data.";
$lang['license_soon_expire'] = "License expires in: {1} You can prolong it just now: <a href=\"http://sklep-sms.pl/index.php?page=zakup\" target=\"_blank\">Prolong License</a>";

$lang['players_flags'] = "Players flags";
$lang['players_services'] = "Players temporary services";
$lang['income'] = "Income";
$lang['settings'] = "Shop settings";
$lang['transaction_services'] = "Payment methods";
$lang['tariffs'] = "Tariffs";
$lang['pricelist'] = "Pricelist";
$lang['users'] = "Users";
$lang['groups'] = "Groups";
$lang['servers'] = "Servers";
$lang['services'] = "Services";
$lang['sms_codes'] = "SMS codes to be used";
$lang['antispam_questions'] = "Antispam questions";
$lang['bought_services'] = "Purchased services";
$lang['payments_sms'] = "SMS payments";
$lang['payments_transfer'] = "Transfer payments";
$lang['payments_wallet'] = "Wallet payments";
$lang['payments_admin'] = "Admin payments";
$lang['logs'] = "Logs";
$lang['update_web'] = "WWW website update";
$lang['update_servers'] = "Servers update";

$lang['amount_of_servers'] = "In shop there are added <strong>{1}</strong> servers.";
$lang['amount_of_users'] = "So far we have <strong>{1}</strong> registered users.";
$lang['amount_of_bought_services'] = "Users have purchased together <strong>{1}</strong> services.";
$lang['amount_of_sent_smses'] = "In total users sent <strong>{1}</strong> SMSes.";

$lang['add_antispam_question'] = "Add antispam question";
$lang['add_service'] = "Add service";
$lang['add_server'] = "Add server";
$lang['add_tariff'] = "Add tariff";
$lang['add_price'] = "Add price";
$lang['add_group'] = "Add group";
$lang['add_sms_code'] = "Add SMS code";

$lang['confirm_remove_server'] = "Do you really want to remove server \n{0}?";

$lang['service_added'] = "Service added successfully.<br />Set service prices in tab <strong>Pricelist</strong><br />
	Whereas in tab <strong>Servers</strong> set servers on which you can buy this service.<br />";

$lang['privilages_names'] = array(
	"acp" => "Access to ACP",
	"manage_settings" => "Settings management",
	"view_groups" => "Groups preview",
	"manage_groups" => "Groups management",
	"view_player_flags" => "Players' flags preview",
	"view_player_services" => "Players' services preview",
	"manage_player_services" => "Players' services management",
	"view_income" => "Income preview",
	"view_users" => "Users preview",
	"manage_users" => "Users management",
	"view_sms_codes" => "SMS codes preview",
	"manage_sms_codes" => "SMS codes management",
	"view_antispam_questions" => "Antispam questions preview",
	"manage_antispam_questions" => "Antispam questions management",
	"view_services" => "Services preview",
	"manage_services" => "Services management",
	"view_servers" => "Servers preview",
	"manage_servers" => "Services management",
	"view_logs" => "Logs preview",
	"manage_logs" => "Logs management",
	"update" => "Scripts update"
);

$lang['no_such_group'] = "Group with such ID does not exist.";
$lang['noaccount_id'] = "Given user's ID is not assigned to any account.";
$lang['no_charge_value'] = "Charge value was not supplied.";
$lang['charge_number'] = "Charge value must be a number.";
$lang['no_service_chosen'] = "No service chosen.";
$lang['no_add_method'] = "Service module doesn't operate admin service adding method.";
$lang['no_edit_method'] = "Service module doesn't operate admin player's service editing method.";
$lang['delete_service'] = "Service removed successfully.";
$lang['no_delete_service'] = "Service not removed.";
$lang['antispam_add'] = "Antispam question added successfully.";
$lang['antispam_edit'] = "Antispam question edited successfully.";
$lang['antispam_no_edit'] = "Could not edit antispam question.";
$lang['delete_antispamq'] = "Antispam question removed successfully.";
$lang['no_delete_antispamq'] = "Antispam question not removed.";
$lang['no_sms_service'] = "No SMS payment service with such ID.";
$lang['no_net_service'] = "No transfer payment service with such ID.";
$lang['no_theme'] = "Given theme does not exist";
$lang['no_language'] = "Given language does not exist";
$lang['settings_edit'] = "Settings edited successfully.";
$lang['settings_no_edit'] = "Settings not edited.";
$lang['payment_edit'] = "Payment method edited successfully.";
$lang['payment_no_edit'] = "Could not edit payment method.";
$lang['no_service_id'] = "No service ID was supplied.";
$lang['long_service_id'] = "Supplied service ID is too long. Maximum 16 chars.";
$lang['id_exist'] = "Service with such ID does not exist.";
$lang['no_service_name'] = "No service name was supplied.";
$lang['field_integer'] = "Field must be an integer.";
$lang['wrong_group'] = "Wrong group was chosen.";
$lang['wrong_module'] = "Wrong module was chosen.";
$lang['service_edit'] = "Service edited successfully.";
$lang['service_no_edit'] = "Service not edited.";
$lang['server_added'] = "Server added successfully.";
$lang['server_edit'] = "Server edited successfully.";
$lang['server_no_edit'] = "Could not edit server.";
$lang['delete_server'] = "Server removed successfully.";
$lang['no_delete_server'] = "Server not removed.";
$lang['nick_taken'] = "Given nickname is already taken.";
$lang['email_taken'] = "Given e-mail is already taken.";
$lang['user_edit'] = "User edited successfully.";
$lang['user_no_edit'] = "Could not edit user.";
$lang['delete_user'] = "User removed successfully.";
$lang['no_delete_user'] = "User not removed.";
$lang['group_add'] = "Group added successfully.";
$lang['group_edit'] = "Group edited successfully.";
$lang['group_no_edit'] = "Could not edit group.";
$lang['delete_group'] = "Group removed successfully.";
$lang['no_delete_group'] = "Group not removed.";
$lang['tariff_exist'] = "Such tariff already exists.";
$lang['tariff_add'] = "Tariff added successfully.";
$lang['tariff_edit'] = "Tariff edited successfully.";
$lang['tariff_no_edit'] = "Could not edit tariff.";
$lang['delete_tariff'] = "Tariff removed successfully.";
$lang['no_delete_tariff'] = "Tariff not removed.";
$lang['no_such_service'] = "Such service does not exist.";
$lang['no_such_server'] = "Such server does not exist.";
$lang['no_such_tariff'] = "Such tariff does not exist.";
$lang['price_add'] = "Price added successfully.";
$lang['price_edit'] = "Price edited successfully.";
$lang['price_no_edit'] = "Could not edit price.";
$lang['delete_price'] = "Price removed successfully.";
$lang['no_delete_price'] = "Price not removed.";
$lang['sms_code_add'] = "SMS code added successfully.";
$lang['delete_sms_code'] = "SMS code removed successfully.";
$lang['no_delete_sms_code'] = "SMS code not removed.";
$lang['delete_log'] = "Log removed successfully.";
$lang['no_delete_log'] = "Log not removed.";
$lang['service_edit_unable'] = "This service cannot be edited.";

$lang['amxx_server'] = "Game server (AMXX)";
$lang['sm_server'] = "Game server (SM)";

$lang['account_charge'] = "Admin {1}({2}) charged user's account: {3}({4}) Amount: {5} {6}";
$lang['account_charge_success'] = "User's account charged successfully: {1} with amount: {2} {3}";
$lang['service_admin_delete'] = "Admin {1}({2}) removed player's service. ID: {3}";
$lang['question_edit'] = "Admin {1}({2}) edited antispam question. ID: {3}";
$lang['question_delete'] = "Admin {1}({2}) removed antispam question. ID: {3}";
$lang['settings_admin_edit'] = "Admin {1}({2}) edited shop settings.";
$lang['payment_admin_edit'] = "Admin {1}({2}) edited payment method. ID: {3}";
$lang['service_admin_add'] = "Admin {1}({2}) added service. ID: {3}";
$lang['service_admin_edit'] = "Admin {1}({2}) edited service. ID: {3}";
$lang['service_admin_delete'] = "Admin {1}({2}) removed service. ID: {3}";
$lang['server_admin_add'] = "Admin {1}({2}) added server. ID: {3}";
$lang['server_admin_edit'] = "Admin {1}({2}) edited server. ID: {3}";
$lang['server_admin_delete'] = "Admin {1}({2}) removed server. ID: {3}";
$lang['user_admin_edit'] = "Admin {1}({2}) edited user. ID: {3}";
$lang['user_admin_delete'] = "Admin {1}({2}) removed user. ID: {3}";
$lang['group_admin_add'] = "Admin {1}({2}) added group. ID: {3}";
$lang['group_admin_edit'] = "Admin {1}({2}) edited group. ID: {3}";
$lang['group_admin_delete'] = "Admin {1}({2}) removed group. ID: {3}";
$lang['tariff_admin_add'] = "Admin {1}({2}) added tariff. ID: {3}";
$lang['tariff_admin_edit'] = "Admin {1}({2}) edited tariff. ID: {3}";
$lang['tariff_admin_delete'] = "Admin {1}({2}) removed tariff. ID: {3}";
$lang['price_admin_edit'] = "Admin {1}({2}) edited price. ID: {3}";
$lang['price_admin_delete'] = "Admin {1}({2}) removed price. ID: {3}";
$lang['sms_code_admin_add'] = "Admin {1}({2}) added SMS code. Code: {3}, Tariff: {4}";
$lang['sms_code_admin_delete'] = "Admin {1}({2}) removed SMS code. ID: {3}";
