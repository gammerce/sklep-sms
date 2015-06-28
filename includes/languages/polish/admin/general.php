<?php

$lang['no_access'] = "Nie masz odpowiednich uprawnień.";


$lang['license_expires'] = "Licencja wygasa";

$lang['remove_install'] = "Usuń folder install !";
$lang['update_available'] = "Dostępna jest aktualizacja skryptu do wersji {1}. Aby ją pobrać przejdź do strony <a href=\"admin.php?pid=update_web\">Aktualizacja strony WWW</a>";
$lang['update_available_servers'] = "Dostępna jest aktualizacja dla {1}/{2} serwerów. Aby ją pobrać przejdź do strony <a href=\"admin.php?pid=update_servers\">Aktualizacja Serwerów</a>";
$lang['license_error'] = "Licencja wygasła lub jest błędna. Przejdź do <a href=\"admin.php?pid=settings\">ustawień</a>, w celu wprowadzenia prawidłowych danych licencji.";
$lang['license_soon_expire'] = "Licencja wygaśnie za: {1} Możesz ją przedłużyć już teraz: <a href=\"http://sklep-sms.pl/index.php?page=zakup\" target=\"_blank\">Przedłuż Licencję</a>";

$lang['players_flags'] = "Flagi graczy";
$lang['players_services'] = "Czasowe usługi graczy";
$lang['income'] = "Przychód";
$lang['settings'] = "Ustawienia sklepu";
$lang['transaction_services'] = "Metody płatności";
$lang['tariffs'] = "Taryfy";
$lang['pricelist'] = "Cennik";
$lang['users'] = "Użytkownicy";
$lang['groups'] = "Grupy";
$lang['servers'] = "Serwery";
$lang['services'] = "Usługi";
$lang['sms_codes'] = "Kody SMS do wykorzystania";
$lang['antispam_questions'] = "Pytania antypamowe";
$lang['bought_services'] = "Kupione usługi";
$lang['payment_sms'] = "Płatności SMS";
$lang['payment_transfer'] = "Płatności internetowe";
$lang['payment_wallet'] = "Płatności z portfela";
$lang['payment_admin'] = "Płatności admina";
$lang['logs'] = "Logi";
$lang['update_web'] = "Aktualizacja strony WWW";
$lang['update_servers'] = "Aktualizacja serwerów";

$lang['amount_of_servers'] = "W sklepie dodanych jest <strong>{1}</strong> serwerów.";
$lang['amount_of_users'] = "Dotychczas zarejestrowało się <strong>{1}</strong> użytkowników.";
$lang['amount_of_bought_services'] = "Użytkownicy zakupili łączenie <strong>{1}</strong> usług.";
$lang['amount_of_sent_smses'] = "W sumie klienci sklepu wysłali aż <strong>{1}</strong> SMSow.";

$lang['add_antispam_question'] = "Dodaj pytanie antyspamowe";
$lang['add_service'] = "Dodaj usługę";
$lang['add_server'] = "Dodaj serwer";
$lang['add_tariff'] = "Dodaj taryfę";
$lang['add_price'] = "Dodaj cenę";
$lang['add_group'] = "Dodaj grupę";
$lang['add_sms_code'] = "Dodaj kod SMS";

$lang['confirm_remove_server'] = "Na pewno chcesz usunąć serwer\n{0}?";

$lang['service_added'] = "Usługa została prawidłowo dodana.<br />Ustaw teraz ceny usługi w zakładce <strong>Cennik</strong><br />
		Natomiast w zakładce <strong>Serwery</strong> ustal na których serwerach będzie można tę usługę zakupić.<br />";

$lang['privilages_names'] = array(
	"acp" => "Dostęp do ACP",
	"manage_settings" => "Zarządzanie ustawieniami",
	"view_groups" => "Przeglądanie grup",
	"manage_groups" => "Zarządzanie grupami",
	"view_player_flags" => "Przeglądanie flag graczy",
	"view_player_services" => "Przeglądanie usług graczy",
	"manage_player_services" => "Zarządzanie usługami graczy",
	"view_income" => "Przeglądanie przychodów",
	"view_users" => "Przeglądanie użytkowników",
	"manage_users" => "Zarządzanie użytkownikami",
	"view_sms_codes" => "Przeglądanie kodów SMS",
	"manage_sms_codes" => "Zarządzanie kodami SMS",
	"view_antispam_questions" => "Przeglądanie pytań anty-spamowych",
	"manage_antispam_questions" => "Zarządzanie pytaniami anty-spamowymi",
	"view_services" => "Przeglądanie usług",
	"manage_services" => "Zarządzanie usługami",
	"view_servers" => "Przeglądanie serwerów",
	"manage_servers" => "Zarządzanie serwerami",
	"view_logs" => "Przeglądanie logów",
	"manage_logs" => "Zarządzanie logami",
	"update" => "Aktualizacja skryptów"
);

$lang['no_such_group'] = "Nie istnieje grupa o takim ID.";
$lang['noaccount_id'] = "Podane ID użytkownika nie jest przypisane do żadnego konta.";
$lang['no_charge_value'] = "Nie podano wartości doładowania.";
$lang['charge_number'] = "Wartość doładowania musi być liczbą.";
$lang['no_service_chosen'] = "Nie wybrano usługi.";
$lang['no_add_method'] = "Moduł usługi nie posiada metody dodawania usługi przez admina.";
$lang['no_edit_method'] = "Moduł usługi nie posiada metody edycji usługi gracza przez admina.";
$lang['delete_service'] = "Usługa gracza została prawidłowo usunięta.";
$lang['no_delete_service'] = "Usługa gracza nie została usunięta.";
$lang['antispam_add'] = "Pytanie anty-spamowe zostało prawidłowo dodane.";
$lang['antispam_edit'] = "Pytanie anty-spamowe zostało prawidłowo wyedytowane.";
$lang['antispam_no_edit'] = "Pytanie anty-spamowe nie zostało prawidłowo wyedytowane.";
$lang['delete_antispamq'] = "Pytanie anty-spamowe zostało prawidłowo usunięte.";
$lang['no_delete_antispamq'] = "Pytanie anty-spamowe nie zostało usunięte.";
$lang['no_sms_service'] = "Brak serwisu płatności SMS o takim ID.";
$lang['no_net_service'] = "Brak serwisu płatności internetowej o takim ID.";
$lang['no_theme'] = "Podany motyw nie istnieje";
$lang['no_language'] = "Podany język nie istnieje";
$lang['settings_edit'] = "Ustawienia zostały prawidłowo wyedytowane.";
$lang['settings_no_edit'] = "Nie wyedytowano ustawień.";
$lang['payment_edit'] = "Metoda płatności została prawidłowo wyedytowana.";
$lang['payment_no_edit'] = "Nie udało się wyedytować metody płatności.";
$lang['no_service_id'] = "Nie wprowadzono ID usługi.";
$lang['long_service_id'] = "Wprowadzone ID usługi jest zbyt długie. Maksymalnie 16 znaków.";
$lang['id_exist'] = "Usługa o takim ID już istnieje.";
$lang['no_service_name'] = "Nie wprowadzono nazwy usługi.";
$lang['field_integer'] = "Pole musi być liczbą całkowitą.";
$lang['wrong_group'] = "Wybrano błędną grupę.";
$lang['wrong_module'] = "Wybrano nieprawidłowy moduł.";
$lang['service_edit'] = "Usługa została prawidłowo wyedytowana.";
$lang['service_no_edit'] = "Usługa nie została wyedytowana.";
$lang['delete_service'] = "Usługa została prawidłowo usunięta.";
$lang['no_delete_service'] = "Usługa nie została usunięta.";
$lang['server_added'] = "Serwer został prawidłowo dodany.";
$lang['server_edit'] = "Serwer został prawidłowo wyedytowany.";
$lang['server_no_edit'] = "Serwer nie został prawidłowo wyedytowany.";
$lang['delete_server'] = "Serwer został prawidłowo usunięty.";
$lang['no_delete_server'] = "Serwer nie został usunięty.";
$lang['nick_taken'] = "Podana nazwa użytkownika jest już zajęta.";
$lang['email_taken'] = "Podany e-mail jest już zajęty.";
$lang['user_edit'] = "Użytkownik został prawidłowo wyedytowany.";
$lang['user_no_edit'] = "Użytkownik nie został prawidłowo wyedytowany.";
$lang['delete_user'] = "Użytkownik został prawidłowo usunięty.";
$lang['no_delete_user'] = "Użytkownik nie został usunięty.";
$lang['group_add'] = "Grupa została prawidłowo dodana.";
$lang['group_edit'] = "Grupa została prawidłowo wyedytowana.";
$lang['group_no_edit'] = "Grupa nie została prawidłowo wyedytowana.";
$lang['delete_group'] = "Grupa została prawidłowo usunięta.";
$lang['no_delete_group'] = "Grupa nie została usunięta.";
$lang['tariff_exist'] = "Taka taryfa już istnieje.";
$lang['tariff_add'] = "Taryfa została prawidłowo dodana.";
$lang['tariff_edit'] = "Taryfa została prawidłowo wyedytowana.";
$lang['tariff_no_edit'] = "Taryfa nie została wyedytowana.";
$lang['delete_tariff'] = "Taryfa została prawidłowo usunięta.";
$lang['no_delete_tariff'] = "Taryfa nie została usunięta.";
$lang['no_such_service'] = "Taka usługa nie istnieje.";
$lang['no_such_server'] = "Taki serwer nie istnieje.";
$lang['no_such_tariff'] = "Taka taryfa nie istnieje.";
$lang['price_add'] = "Cena została prawidłowo dodana.";
$lang['price_edit'] = "Cena została prawidłowo wyedytowana.";
$lang['price_no_edit'] = "Cena nie została wyedytowana.";
$lang['delete_price'] = "Cena została prawidłowo usunięta.";
$lang['no_delete_price'] = "Cena nie została usunięta.";
$lang['SMScode_add'] = "Kod SMS został prawidłowo dodany.";
$lang['delete_SMScode'] = "Kod SMS został prawidłowo usunięty.";
$lang['no_delete_SMScode'] = "Kod SMS nie został usunięty.";
$lang['delete_log'] = "Log został prawidłowo usunięty.";
$lang['no_delete_log'] = "Log nie został usunięty.";
$lang['service_edit_unable'] = "Tej usługi nie da rady edytować.";