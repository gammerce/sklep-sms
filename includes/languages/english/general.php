<?php

$l['forgotten_password'] = "Password recovery";
$l['user_own_services'] = "My current services";
$l['title_payment'] = "Payment";
$l['payment_log'] = "Payment log";
$l['purchase'] = "Service purchase";
$l['register'] = "Registration";
$l['reset_password'] = "Reset password";
$l['transfer_finalized'] = "Transaction finalized";

$l['welcome_message'] = "Welcome to the web store!";

$l['register_vert'] = "R<br />E<br />G<br />I<br />S<br />T<br />E<br />R";
$l['login_vert'] = "L<br />O<br />G<br /><br />I<br />N";

$l['repeat'] = "Repeat";
$l['send'] = "Send";
$l['clear'] = "Clear";
$l['name'] = "Name";
$l['surname'] = "Surname";
$l['username'] = "Username";
$l['password_repeat'] = "Repeat password";
$l['forgot_password'] = "I don't remeber password";
$l['email'] = "E-mail";
$l['email_repeat'] = "Repeat e-mail";
$l['log_in'] = "Log in";
$l['nick'] = "Nickname";
$l['ip'] = "IP";
$l['expire'] = "Expires";
$l['description'] = "Description";
$l['cost'] = "Cost";
$l['price'] = "Price";
$l['question'] = "Question";
$l['answer'] = "Answer";
$l['contact'] = "Contact";
$l['regulations'] = "Regulations";

$l['old_password'] = "Old Password";
$l['new_password'] = "New Password";

$l['required_data'] = "Required data";
$l['optional_data'] = "Optional data";
$l['antispam_question'] = "Antispam question";
$l['create_account'] = "Create account";

$l['go_to_payment'] = "Proceed to payment";
$l['purchase_form_validated'] = "Given data are correct. Now choose payment method.";
$l['order_details'] = "Order details";
$l['payment_sms'] = "SMS payment";
$l['payment_transfer'] = "Transfer payment";
$l['payment_wallet'] = "Wallet payment";
$l['got_code'] = "I've got code!";

$l['pay_sms'] = "I pay by SMS";
$l['pay_transfer'] = "I pay by Transfer";
$l['pay_wallet'] = "I pay by Wallet";

$l['take_over'] = "Take over";

$l['way_of_payment'] = "Payment method";
$l['choose_payment'] = "Choose payment";
$l['admin'] = "Admin";
$l['wallet'] = "Wallet";

$l['choose_type'] = "Choose type";
$l['choose_server'] = "Choose server";
$l['choose_amount'] = "Choose amount";

$l['transfer_cost'] = "Transfer cost";
$l['sms_cost'] = "SMS cost";

$l['transfer_unavailable'] = "Payment by transfer unavailable.";
$l['sms_unavailable'] = "Payment by SMS unavailable.";

$l['my_services'] = "My services";
$l['change_password'] = "Change password";
$l['take_over_service'] = "Take over service";

$l['transfer'] = "Transfer";

$l['transfer_id'] = "Payment ID";

$l['transfer_error'] = "Unfortunately, transfer payment failed.";
$l['transfer_unverified'] = "Unfortunately, transfer data failed to be verified correctly.";

$l['contact_info'] = "You can contact us by:";

$l['restore_password_info'] = "In order to restore password, give your address <strong>e-mail</strong> or <strong>nickname</strong>.<br />
At the next step, an e-mail with a link to restore password will be send to you.";

$l['must_be_logged_out'] = "You cannot browse this page. You're logged in.";
$l['must_be_logged_in'] = "You cannot browse this page. You're not loggedd in.";

$l['no_reset_key'] = "No reset key was given.";
$l['wrong_reset_key'] = "Reset key incorrect.<br />
Contact our service administrator to receive more information.";
$l['password_changed'] = "Password was changed successfully.";

$l['wrong_id'] = "Wrong ID";
$l['site_not_exists'] = "Site does not exist.";

$l['payment_for_service'] = "Payment for service: {1}";
$l['service_was_bought'] = "Service {1} was purchased on server {2}";
$l['wallet_charged'] = "Wallet was charged.";
$l['wallet_was_charged'] = "Wallet was charged with {1}";
$l['charge_wallet'] = "Wallet Charge";

$l['add_code_to_reuse'] = "Code was added to the list of codes to be used. Code: {1} Tariff: {2}." .
	"An attempt to use it by {3}({4})({5}) with service purchase, tariff: {6}.";
$l['bad_sms_code_used'] = "SMS transaction of user: {1}({2})({3}) failed. Used return code: {4} Content: {5} Number: {6} Error code: {7}";

$l['type_setinfo'] = "Type in the console: setinfo _ss \"{1}\"";

$l['sms']['send_sms'] = "Send SMS";
$l['sms']['text'] = "Text";
$l['sms']['number'] = "Number";
$l['sms']['return_code'] = "Return code";
$l['sms']['info']['ok'] = "Given return code is correct.";
$l['sms']['info']['bad_code'] = "Given return code is just incorrect.";
$l['sms']['info']['bad_number'] = "Code is OK, but unfortunately obtained by sending SMS on different number.";
$l['sms']['info']['bad_api'] = "Given API is incorrect.";
$l['sms']['info']['bad_email'] = "E-mail given in payment configuration is incorrect.";
$l['sms']['info']['server_error'] = "Given API is incorrect, given return code was wrong or another error occured.";
$l['sms']['info']['service_error'] = "Incorrectly set up service, contact the shop owner.";
$l['sms']['info']['error'] = "An error occured. Cause is unknown.";
$l['sms']['info']['no_connection'] = "No connection to the verification script.";
$l['sms']['info']['bad_data'] = "Not all the necessary data were given in payment configuration.";
$l['sms']['info']['dunno'] = "An unknown error occured. Report it to the shop owner.";
$l['sms']['info']['no_sms_serve'] = "This service does not operate SMS payment. Report the error to the shop owner.";
$l['no_transfer_serve'] = "This service does not operate transfer payment. Report the error to the shop owner.";
$l['transfer_ok'] = "Payment preparation successful.<br />In a minute you will be send to transaction service.";

$l['bad_service_code'] = "Code is wrong or doesn't match purchase details.";
$l['service_no_permission'] = "You have no permission to use this service.";

$l['value_must_be_ge_than'] = "Value must me greater or equal to {1}.";

$l['no_login_password'] = "Unfortunately, without providing nickname and login, you can't log in.";
$l['login_success'] = "Logging in successful.";
$l['bad_pass_nick'] = "Unfortunately, passowrd or/and nickname are incorrect.";
$l['logout_success'] = "Logging out successful.";
$l['nick_occupied'] = "Given nickname is already taken.";
$l['different_pass'] = "Given passwords are different.";
$l['email_occupied'] = "Given e-mail is already taken.";
$l['different_email'] = "Given e-mails are different.";
$l['wrong_anti_answer'] = "Wrong answer to the antispam question.";
$l['register_success'] = "Account registered successfully. In a moment you will be automatically logged in.";
$l['nick_no_account'] = "Given nickname is not assigned to any account.";
$l['email_no_account'] = "Given e-mail is not assigned to any account.";
$l['keyreset_error'] = "An error occured while sending e-mail with password reset link.";
$l['wrong_sender_email'] = "E-mail assigned you your account is incorrect. Report it to the shop owner.";
$l['email_sent'] = "E-mail password reset link has been sent to your mailbox.";
$l['old_pass_wrong'] = "Old password is incorrect.";

$l['wrong_payment_method'] = "Wrong payment method was chosen.";
$l['no_login_no_wallet'] = "You can't pay by Wallet when not logged in.";
$l['no_sms_payment'] = "You can't pay by SMS for service quantity. Choose another payment method.";
$l['purchase_success'] = "Service purchased successfully.";

$l['not_enough_money'] = "Ups! You don't have enough money in wallet. Charge wallet ;-)";

$l['new_account'] = "Create new account. ID: {1} User name: {2}, IP: {3}";
$l['reset_key_email'] = "E-mail with password reset key has been sent. User: {1}({2}) E-mail: {3} Form data. User name: {4} E-mail: {5}";
$l['reset_pass'] = "Password has been reset. User ID: {1}.";

$l['transfer_above_amount'] = "You can pay by transfer only for purchase above 1.00 {1}";

$l['payment_accepted'] = "Payment for service accepted: {1} Amount: {2} Transaction ID: {3} Service: {4} {5}({6})({7})";
$l['transfer_accepted'] = "Transfer payment: {1} was accepted,but service module {2} was programmed incorrectly and purchase failed.";
$l['payment_not_accepted'] = "Transaction authorization failure: {1} Amount: {2} Service: {3} {4}({5})({6})";

$l['purchase_code'] = "Code{1} was used to purchase service by {2}({3}). Payment ID: {4}";

$l['service_not_displayed'] = "Service cannot be displayed, because its module does not provide such functionality.";