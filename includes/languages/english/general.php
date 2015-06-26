<?php

$lang['forgotten_password'] = "Password recovery";
$lang['my_current_services'] = "My current services";
$lang['title_payment'] = "Psyment";
$lang['payment_log'] = "Payment log";
$lang['purchase'] = "Service purchase";
$lang['register'] = "Registration";
$lang['reset_password'] = "Reset password";
$lang['take_over_service'] = "Take over service";
$lang['transfer_finalized'] = "Transaction finalized";

$lang['welcome_message'] = "Welcome to the web store!";

$lang['register_vert'] = "R<br />E<br />G<br />I<br />S<br />T<br />E<br />R";
$lang['login_vert'] = "L<br />O<br />G<br /><br />I<br />N";

$lang['repeat'] = "Reapeat";
$lang['send'] = "Send";
$lang['clear'] = "Clear";
$lang['name'] = "Name";
$lang['surname'] = "Surname";
$lang['username'] = "Username";
$lang['password_repeat'] = "Repeat password";
$lang['forgot_password'] = "I don't remeber password";
$lang['email'] = "E-mail address";
$lang['email_repeat'] = "Reapeat e-mail address";
$lang['log_in'] = "Log in";
$lang['service'] = "Service";
$lang['nickipsid'] = "Nickname / IP / SteamID";
$lang['nick'] = "Nickname";
$lang['ip'] = "IP";
$lang['sid'] = "SteamID";
$lang['server'] = "Server";
$lang['expire'] = "Expires";
$lang['date'] = "Date";
$lang['description'] = "Desription";
$lang['cost'] = "Cost";
$lang['price'] = "Price";
$lang['amount'] = "Quantity";
$lang['question'] = "Question";
$lang['answer'] = "Answer";
$lang['contact'] = "Contact";
$lang['regulations'] = "Regulations";

$lang['old_password'] = "Old Password";
$lang['new_password'] = "New Password";

$lang['required_data'] = "Required data";
$lang['optional_data'] = "Optional data";
$lang['antispam_question'] = "Antispam question";
$lang['create_account'] = "Create account";

$lang['go_to_payment'] = "Proceed to payment";
$lang['purchase_form_validated'] = "Given data are correct. Now choose payment method.";
$lang['order_details'] = "Order details";
$lang['payment_sms'] = "SMS payment";
$lang['payment_transfer'] = "Transfer payment";
$lang['payment_wallet'] = "Wallet payment";

$lang['pay_sms'] = "I pay by SMS";
$lang['pay_transfer'] = "I pay by Transfer";
$lang['pay_wallet'] = "I pay by Wallet";

$lang['take_over'] = "Take over";

$lang['way_of_payment'] = "Payment method";
$lang['choose_payment'] = "Chose payment";
$lang['admin'] = "Admin";
$lang['wallet'] = "Wallet";

$lang['choose_type'] = "Choose type";
$lang['choose_server'] = "Choose server";
$lang['choose_service'] = "Choose service";
$lang['choose_amount'] = "Choose amount";

$lang['transfer_cost'] = "Transfer cost";
$lang['sms_cost'] = "SMS cost";

$lang['transfer_unavailable'] = "Payment by transfer unavailable.";
$lang['sms_unavailable'] = "Payment by SMS unavailable.";

$lang['my_services'] = "My services";
$lang['change_password'] = "Change password";
$lang['take_over_service'] = "Take over service";

$lang['transfer'] = "Transfer";

$lang['transfer_id'] = "Payment ID";
$lang['transfer_error'] = "An error occured while receiving transfer data.";

$lang['transfer_error'] = "Unfortunately, transfer payment failed.";
$lang['transfer_unverified'] = "Unfortunately, transfer data failed to be verified correctly.";

$lang['contact_info'] = "You can contact us by methods below.";

$lang['restore_password_info'] = "In order to restore password, give you address <strong>e-mail address</strong> or <strong>nickname</strong>.<br />
At the next step, an e-mail with a link to restore password will be send to you.";

$lang['must_be_logged_out'] = "You cannot browse this page. You're logged in.";
$lang['must_be_logged_in'] = "You cannot browse this page. You're not loggedd in.";

$lang['no_reset_key'] = "No reset key was given.";
$lang['wrong_reset_key'] = "Reset key incorrect.<br />
Contact our service administrator to receive more information.";
$lang['password_changed'] = "Password was changed successfully.";

$lang['wrong_id'] = "Wrong ID";
$lang['site_not_exists'] = "Site does not exist.";

$lang['payment_for_service'] = "Payment for service: {1}";
$lang['service_was_bought'] = "Service was purchased {1} on server {2}";
$lang['wallet_charged'] = "Wallet was charged.";
$lang['wallet_was_charged'] = "Wallet was charged with {1}";
$lang['bought_service'] = "Service Purchase";
$lang['charge_wallet'] = "Wallet Charge";

$lang['add_code_to_reuse'] = "Code was added to the list of codes to be used. Code: {1} Tariff: {2}." .
	"An attempt to use it by {3}({4})({5}) with service purchase, tariff: {6}.";
$lang['bad_sms_code_used'] = "SMS transaction of user: {1}({2})({3}) failed. Used return code: {4} Content: {5} Number: {6} Error code: {7}";

$lang['type_setinfo'] = "Type in the console: setinfo _ss \"{1}\"";

$lang['sms']['send_sms'] = "Send SMS";
$lang['sms']['text'] = "Saying";
$lang['sms']['on'] = "On";
$lang['sms']['return_code'] = "Return code";
$lang['sms']['info']['ok'] = "Given return code is correct.";
$lang['sms']['info']['bad_code'] = "Given return code is just incorrect.";
$lang['sms']['info']['bad_number'] = "Code is OK, but unfortunately obtained by sending SMS on different number.";
$lang['sms']['info']['bad_api'] = "Given API is incorrect.";
$lang['sms']['info']['bad_email'] = "E-mail address given in payment configuration in incorrect.";
$lang['sms']['info']['server_error'] = "Given API is incorrect, given return code was wrong or another error occured.";
$lang['sms']['info']['service_error'] = "Incorrectly set up service, contact shop owner.";
$lang['sms']['info']['error'] = "An error occured. Cause is unknown.";
$lang['sms']['info']['no_connection'] = "No connection to the verification script.";
$lang['sms']['info']['bad_data'] = "Not all the necessary data were given in payment configuration.";
$lang['sms']['info']['dunno'] = "An unknown error occured. Report it to the shop owner.";
$lang['sms']['info']['no_sms_serve'] = "This service does not operate SMS payment. Report the error to the shop owner.";
$lang['no_transfer_serve'] = "This service does not operate transfer payment. Report the error to the shop owner.";
$lang['transfer_ok'] = "Payment preparation successful.<br />In a minute you will be send to transaction service.";

$lang['service_no_permission'] = "You have no permission to use this service.";

$lang['value_must_be_ge_than'] = "Value must me greater or equal to {1}.";