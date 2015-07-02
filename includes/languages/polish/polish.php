<?php

$lang['shop_sms'] = "Sklep SMS";
$lang['logout'] = "Wyloguj";
$lang['go'] = "Idź";
$lang['and'] = "i";
$lang['yes'] = "Tak";
$lang['no'] = "Nie";
$lang['type'] = "Typ";
$lang['password'] = "Hasło";
$lang['none'] = "Brak";
$lang['bought_date'] = "Data zakupu";
$lang['no_data'] = "Brak danych";
$lang['choose_option'] = "Wybierz opcję";
$lang['forever'] = "Na zawsze";
$lang['edit'] = "Edytuj";
$lang['delete'] = "Usuń";

$lang['days'] = "dni";
$lang['hours'] = "godzin";
$lang['minutes'] = "minut";
$lang['seconds'] = "sekund";
$lang['never'] = "Nigdy";

$lang['months'] = array("", "Styczeń", "Luty", "Marzec", "Kwiecień", "Maj", "Czerwiec", "Lipiec", "Sierpień", "Wrzesień", "Październik", "Listopad", "Grudzień");

$lang['verification_error'] = "Coś poszło nie tak podczas łączenia się z serwerem weryfikacyjnym.";

$lang['main_page'] = "Strona główna";
$lang['acp'] = "Panel Admina";
$lang['acp_short'] = "PA";
$lang['no_privilages'] = "Nie masz dostępu do tego miejsca, więc tu nie zerkaj!";
$lang['wrong_login_data'] = "Login i/lub hasło są nieprawidłowe.";

$lang['wrong_cron_key'] = "Nie tędy droga hakierze.";

$lang['email_was_sent'] = "Wysłano e-maila na adres: {1} o treści: {2}";

$lang['username_chars_warn'] = "Nazwa użytkownika zawiera niedozwolone znaki (<,>,&,\")";
$lang['wrong_email'] = "Wprowadzony adres e-mail jest nieprawidłowy.";
$lang['wrong_ip'] = "Wprowadzony adres IP jest nieprawidłowy.";
$lang['wrong_sid'] = "Wprowadzony Steam ID jest nieprawidłowy.";
$lang['return_code_length_warn'] = "Wprowadzony kod zwrotny jest nieco przydługaśny.";
$lang['field_no_empty'] = "Pole nie może być puste.";
$lang['field_must_be_number'] = "W polu musi się znajdować liczba.";
$lang['field_length_min_warn'] = "Pole musi się składać z co najmniej {1} znaków.";
$lang['field_length_max_warn'] = "Pole może się składać z co najwyżej {1} znaków.";
$lang['value_must_be_positive'] = "Wartość musi być dodatnia.";

$lang['mysqli']['no_server_connection'] = "Nie można utworzyć połączenia z serwerem bazy danych!";
$lang['mysqli']['no_db_connection'] = "Nie można połączyć się z bazą danych!";
$lang['mysqli']['query_error'] = "Wystąpił błąd w zapytaniu do bazy danych.";
$lang['mysqli']['no_query_num_rows'] = "Nie można otrzymać liczby wierszy, ponieważ id zapytania nie zostało podane!";
$lang['mysqli']['no_query_fetch_array'] = "Nie można pozyskać tablicy, ponieważ id zapytania nie zostało podane!";
$lang['mysqli']['no_query_fetch_array_assoc'] = "Nie można pobrać tablicy asocjacyjnej, ponieważ id zapytania nie zostało podane!";

$lang['sth_went_wrong'] = "Coś poszło nie tak :/";
$lang['ajax_error'] = "Wystąpił błąd podczas pozyskiwania danych.";

$lang['bought_service_info'] = "Zakupiono usługę {1}. Dane: {2} Ilość: {3} Serwer: {4} ID transakcji: {5}. Email: {6} {7}({8})({9})";

$lang['payment']['bad_type'] = "Błąd w konstruktorze klasy Payment. Niedozwolony typ płatności.";
$lang['payment']['bad_service'] = "API usługi {1} nie zostało zaimplenetowane w kodzie.";
$lang['payment']['remove_code_from_db'] = "Usunięto kod z tabeli kodów do wykorzystania. Kod: {1} Taryfa: {2}";

$lang['form_wrong_filled'] = "Nie wszystkie pola formularza zostały prawidłowo wypełnione.";

$lang['nickpass'] = "Nick + Hasło";
$lang['ippass'] = "IP + Hasło";
$lang['sid'] = "SteamID";

$lang['not_logged'] = "Nie jesteś zalogowany/a";
$lang['logged'] = "Jesteś zalogowany/a";
$lang['dont_play_games'] = "Nie kombinuj...";
$lang['service_cant_be_modified'] = "Tej usługi nie można edytować.";
$lang['bad_module'] = "Moduł usługi został źle zaprogramowany.";
$lang['wrong_sign'] = "Coś tu nie gra, weryfikacja danych zakończyła się informatyczną klęską.";
$lang['no_service'] = "Nie ma usługi o takim id.";
$lang['service_isnt_yours'] = "Istnieje już usługa na takie dane, lecz nie należy ona do Ciebie.";
$lang['only_yes_no'] = "Pole może przyjąć tylko wartości: 'TAK' i 'NIE'";

$lang['edited_user_service'] = "Prawidłowo wyedytowano usługę.";
$lang['not_edited_user_service'] = "Nie udało się wyedytować usługi.";

$lang['you_arent_logged'] = "Coś tu nie gra, nie jesteś zalogowany/a oO";
$lang['not_logged_or_no_perm'] = "Coś tu nie gra, nie jesteś zalogowany/a lub nie masz odpowiednich uprawnień oO";

$lang['languages'] = array(
	'polish' => "Polski",
	'english' => "English"
);

$lang['expired_service_delete'] = "AUTOMAT: Usunięto wygasłą usługę gracza. Auth Data: {1} Serwer: {2} Usługa: {3} Typ: {4)";