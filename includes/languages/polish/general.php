<?php

$lang['welcome_message'] = "Witaj w sklepie internetowym!";

$lang['register_vert'] = "Z<br />A<br />R<br />E<br />J<br />E<br />S<br />T<br />R<br />U<br />J";
$lang['login_vert'] = "Z<br />A<br />L<br />O<br />G<br />U<br />J";

$lang['repeat'] = "Powtórz";
$lang['send'] = "Wyślij";
$lang['clear'] = "Wyczyść";
$lang['name'] = "Imię";
$lang['surname'] = "Nazwisko";
$lang['username'] = "Nazwa Użytkownika";
$lang['password_repeat'] = "Powtórz Hasło";
$lang['forgot_password'] = "Nie pamiętam hasła";
$lang['email'] = "Adres e-mail";
$lang['email_repeat'] = "Powtórz adres e-mail";
$lang['log_in'] = "Zaloguj";
$lang['service'] = "Usługa";
$lang['nickipsid'] = "Nick / IP / SteamID";
$lang['nick'] = "Nick";
$lang['ip'] = "IP";
$lang['sid'] = "SteamID";
$lang['server'] = "Serwer";
$lang['expire'] = "Wygasa";
$lang['date'] = "Data";
$lang['description'] = "Opis";
$lang['cost'] = "Koszt";
$lang['price'] = "Cena";
$lang['amount'] = "Ilość";
$lang['question'] = "Pytanie";
$lang['answer'] = "Odpowiedź";
$lang['contact'] = "Kontakt";
$lang['regulations'] = "Regulamin";

$lang['old_password'] = "Stare Hasło";
$lang['new_password'] = "Nowe Hasło";

$lang['required_data'] = "Wymagane dane";
$lang['optional_data'] = "Opcjonalne dane";
$lang['antispam_question'] = "Pytanie Antyspamowe";
$lang['create_account'] = "Załóż Konto";

$lang['go_to_payment'] = "Przejdź do płatności";
$lang['purchase_form_validated'] = "Wprowadzone dane są prawidłowe. Wybierz teraz sposób płatności.";
$lang['order_details'] = "Szczegóły zamówienia";
$lang['payment_sms'] = "Płatność SMS";
$lang['payment_transfer'] = "Płatność przelew";
$lang['payment_wallet'] = "Płatność portfel";

$lang['pay_sms'] = "Płacę SMSem";
$lang['pay_transfer'] = "Płacę Przelewem";
$lang['pay_wallet'] = "Płacę z Portfela";

$lang['take_over'] = "Przejmij";

$lang['way_of_payment'] = "Sposób płatności";
$lang['choose_payment'] = "Wybierz płatność";
$lang['admin'] = "Admin";
$lang['wallet'] = "Portfel";

$lang['choose_type'] = "Wybierz rodzaj";
$lang['choose_server'] = "Wybierz serwer";
$lang['choose_service'] = "Wybierz usługę";
$lang['choose_amount'] = "Wybierz ilość";

$lang['transfer_cost'] = "Koszt Przelewu";
$lang['sms_cost'] = "Koszt SMS";

$lang['transfer_unavailable'] = "Nie można dokonać płatności za pomocą przelewu.";
$lang['sms_unavailable'] = "Nie można dokonać płatności za pomocą SMSa.";

$lang['my_services'] = "Moje usługi";
$lang['change_password'] = "Zmień hasło";
$lang['take_over_service'] = "Przejmij usługę";

$lang['transfer'] = "Przelew";

$lang['transfer_id'] = "ID Płatności";
$lang['transfer_error'] = "Wystąpił błąd podczas przyjmowania danych o przelewie.";

$lang['transfer_error'] = "Niestety, ale płatność za pomocą przelewu zakończyła się niepowodzeniem.";
$lang['transfer_unverified'] = "Niestety, ale udało się zweryfikować poprawności danych przelewu.";

$lang['contact_info'] = "Możesz się z nami skontaktować na jeden z poniższych sposobów.";

$lang['restore_password_info'] = "W celu odzyskania hasła, podaj swój adres <strong>adres e-mail</strong> lub <strong>nazwę użytkownika</strong>.<br />
W kolejnym etapie, zostanie do Ciebie wysłany e-mail z linkiem do zresetowania hasła.";

$lang['must_be_logged_out'] = "Nie możesz przeglądać tej strony. Jesteś zalogowany/a.";
$lang['must_be_logged_in'] = "Nie możesz przeglądać tej strony. Nie jesteś zalogowany/a.";

$lang['no_reset_key'] = "Nie podano kodu resetowania hasła.";
$lang['wrong_reset_key'] = "Kod resetowania hasła jest błędny.<br />
Skontaktuj się z administratorem serwisu w celu uzyskania dodatkowych informacji.";

$lang['wrong_id'] = "Błędne ID";
$lang['site_not_exists'] = "Strona nie istnieje.";

$lang['payment_for_service'] = "Płatność za usługę: {1}";
$lang['service_was_bought'] = "Zakupiono usługę {1} na serwerze {2}";
$lang['wallet_charged'] = "Portfel został doładowany.";
$lang['wallet_was_charged'] = "Doładowano portfel kwotą {1}";
$lang['bought_service'] = "Zakup Usługi";
$lang['charge_wallet'] = "Doładowanie Portfela";

$lang['add_code_to_reuse'] = "Dodano kod do tabeli kodów do wykorzystania. Kod: {1} Taryfa: {2}." .
	"Próba użycia go przez {3}({4})({5}) przy zakupie usługi o taryfie: {6}.";
$lang['bad_sms_code_used'] = "Transakcja SMS użytkownika: {1}({2})({3}) nie powiodła się. Użyto kodu zwrotnego: {4} Treść: {5} Numer: {6} Kod błędu: {7}";

$lang['type_setinfo'] = "Wpisz w konsoli: setinfo _ss \"{1}\"";

$lang['sms']['send_sms'] = "Wyślij SMSa";
$lang['sms']['text'] = "O treści";
$lang['sms']['on'] = "Na numer";
$lang['sms']['return_code'] = "Kod zwrotny";
$lang['sms']['info']['ok'] = "Wprowadzono prawidłowy kod zwrotny.";
$lang['sms']['info']['bad_code'] = "Wprowadzony kod zwrotny jest zwyczajnie błędny.";
$lang['sms']['info']['bad_number'] = "Kod jest dobry, lecz niestety został uzyskany poprzez wysłanie SMSa na inny numer.";
$lang['sms']['info']['bad_api'] = "Podane API jest nieprawidłowe.";
$lang['sms']['info']['bad_email'] = "Podany email w konfiguracji płatności jest nieprawidłowy.";
$lang['sms']['info']['server_error'] = "Podane API jest nieprawidłowe, podano zły kod zwrotny lub wystapil jeszcze inny blad.";
$lang['sms']['info']['service_error'] = "Nieprawidłowo skonfigurowana usługa, skontaktuj się z właścicielem sklepu.";
$lang['sms']['info']['error'] = "Wystąpił błąd. Przyczyny nie są znane.";
$lang['sms']['info']['no_connection'] = "Nie można się połączyć ze skryptem weryfikacyjnym.";
$lang['sms']['info']['bad_data'] = "Nie podano wszystkich potrzebnych danych w konfiguracji płatności.";
$lang['sms']['info']['dunno'] = "Wystąpił nieznany błąd. Zgłoś go właścicielowi sklepu.";
$lang['sms']['info']['no_sms_serve'] = "Dany serwis nie obsługuje płatności SMS. Zgłoś błąd właścicielowi sklepu.";
$lang['no_transfer_serve'] = "Dany serwis nie obsługuje płatności przelewem. Zgłoś błąd właścicielowi sklepu.";
$lang['transfer_ok'] = "Przygotowanie płatności przebiegło pomyślnie.<br />Za chwilę nastąpi przekierowanie do serwisu transakcyjnego.";

$lang['service_no_permission'] = "Nie masz uprawnień, aby móc korzystać z tej usługi.";

$lang['value_must_be_ge_than'] = "Wartość musi być większa lub równa {1}.";