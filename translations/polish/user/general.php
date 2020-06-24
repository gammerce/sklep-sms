<?php

return [
    'admin' => 'Admin',
    'answer' => 'Odpowiedź',
    'available_services' => 'Dostępne usługi',
    'change_password' => 'Zmiana hasła',
    'change_password_subtitle' =>
        'W celu zmiany hasła do swojego konta, podaj stare hasło. Następnie wpisz nowe hasło oraz wprowadź je ponownie.',
    'charge_wallet' => 'Doładuj portfel',
    'choose_payment' => 'Wybierz metodę płatność',
    'choose_payment_module' => 'Wybierz moduł płatności',
    'clear' => 'Wyczyść',
    'contact_info' => 'Możesz się z nami skontaktować na jeden z poniższych sposobów.',
    'create_account' => 'Załóż Konto',
    'different_values' => 'Podane wartości różnią się.',
    'direct_billing_unavailable' => 'Nie można wykonać płatności przy pomocy direct billing.',
    'email_occupied' => 'Podany e-mail jest już zajęty.',
    'email_repeat' => 'Powtórz adres e-mail',
    'email_sent' =>
        'Jeżeli podane dane są prawidłowe, to e-mail wraz z linkiem do zresetowania hasła został właśnie wysłany na Twoją skrzynkę pocztową.',
    'expire' => 'Wygasa',
    'external_payment_prepared' =>
        'Przygotowanie płatności przebiegło pomyślnie.<br />Za chwilę nastąpi przekierowanie do serwisu transakcyjnego.',
    'forgot_password' => 'Nie pamiętam hasła',
    'forgotten_password' => 'Odzyskanie hasła',
    'go_to_payment' => 'Przejdź do płatności',
    'invalid_promo_code' => 'Nieprawidłowy kod promocyjny',
    'keyreset_error' => 'Wystąpił błąd podczas wysyłania e-maila z linkiem do zresetowania hasła.',
    'languages' => 'Języki',
    'log_accepted_sms_code' => 'Zaakceptowana kod SMS [{1}] Treść: [{2}] Numer: [{3}]',
    'log_add_code_to_reuse' =>
        'Dodano kod do tabeli kodów do wykorzystania. Kod: [{1}] Kwota SMS: [{2}] Pczekiwana kwota SMS: [{3}]',
    'log_bad_sms_code_used' =>
        'Transakcja SMS nie powiodła się. Użyto kodu zwrotnego: [{1}] Treść: [{2}] Numer: [{3}] Kod błędu: [{4}]',
    'log_external_payment_accepted' =>
        'Zaakceptowano płatność. Metoda [{1}] ID zakupu [{2}] ID transakcji: [{3}] Kwota: [{4}] Usługa: [{5}]',
    'log_external_payment_invalid_amount' =>
        'Zapłacona kwota różni się od kwoty zakupu. Metoda: [{1}] ID: [{2}] Zapłacona kwota: [{3}] Oczekiwana kwota: [{4}]',
    'log_external_payment_invalid_module' =>
        'Płatność: [{1}] została zaakceptowana, jednakże moduł usługi [{2}] nie implementuje interfejsu IServicePurchase.',
    'log_external_payment_no_transaction_file' =>
        'Nie znaleziono pliku [{1}] z danymi transakcji dla płatności: [{2}]',
    'log_external_payment_not_accepted' =>
        'Nieudana autoryzacja transakcji. Metoda [{1}] ID transakcji [{2}] Kwota: [{3}] Usługa: [{4}]',
    'log_new_account' => 'Założono nowe konto. ID: [{1}] Nazwa Użytkownika: [{2}], IP: [{3}]',
    'log_password_changed' => 'Użytkownik zmienił swoje hasło.',
    'log_purchase_code' => 'Wykorzystano kod [{1}] do zakupu usługi. ID płatności: [{2}]',
    'log_reset_key_email' =>
        'Wysłano e-maila z kodem do zresetowania hasła. Użytkownik: [{1}][{2}] E-mail: [{3}] Dane formularza. Nazwa użytkownika: [{4}] E-mail: [{5}]',
    'log_reset_pass' => 'Zresetowano hasło. ID Użytkownika: {1}.',
    'login_success' => 'Logowanie przebiegło bez większych trudności.',
    'login_vert' => 'Z<br />A<br />L<br />O<br />G<br />U<br />J',
    'logout_success' => 'Wylogowywanie przebiegło bez większych trudności.',
    'my_services' => 'Moje usługi',
    'nick_occupied' => 'Podana nazwa użytkownika jest już zajęta.',
    'no_login_no_wallet' => 'Nie można zapłacić portfelem, gdy nie jesteś zalogowany.',
    'no_login_password' =>
        'No niestety, ale bez podania nazwy użytkownika oraz loginu, nie zalogujesz się.',
    'no_reset_key' => 'Nie podano kodu resetowania hasła.',
    'not_enough_money' =>
        'Bida! Nie masz wystarczającej ilości kasy w portfelu. Doładuj portfel ;-)',
    'old_pass_wrong' => 'Stare hasło jest nieprawidłowe.',
    'optional_data' => 'Opcjonalne dane',
    'order_details' => 'Szczegóły zamówienia',
    'pages' => 'Strony',
    'pass_changed' => 'Hasło zostało prawidłowo zmienione.',
    'password_changed' => 'Hasło zostało prawidłowo zmienione.',
    'password_repeat' => 'Powtórz Hasło',
    'password_reset' =>
        'W celu zmiany hasła do swojego konta, podaj nowe hasło, a następnie wpisz je ponownie.',
    'pay_direct_billing' => 'Płacę z Direct Billing',
    'pay_sms' => 'Płacę SMSem',
    'pay_transfer' => 'Płacę z {1}',
    'pay_wallet' => 'Płacę z portfela',
    'payment_direct_billing' => 'Direct Billing',
    'payment_for_service' => 'Płatność za usługę: {1}',
    'payment_log' => 'Historia płatności',
    'payment_method_unavailable' =>
        'Nie można zapłacić tą metodą płatności za tę ilość usługi. Wybierz inną metodę płatności.',
    'payment_option_transfer' => 'Przelew ({1})',
    'payment_rejected' => 'Płatność odrzucona',
    'payment_sms' => 'SMS',
    'payment_success' => 'Płatność zaakceptowana',
    'payment_success_subtitle' => 'Dziękujemy! Twoja płatność zakończyła się pomyślnie.',
    'payment_success_content' =>
        'Swoją usługę otrzymasz po potwierdzeniu płatności przez operatora. Potrwa to do 15 minut.',
    'payment_transfer' => 'Przelew ({1})',
    'payment_wallet' => 'Portfel',
    'price' => 'Cena',
    'profile' => 'Profil',
    'profile_edit' => 'Profil został zaktualizowany.',
    'profile_subtitle' => 'Powiedz nam nieco więcej o sobie.',
    'promo_code' => 'Kod promocyjny',
    'purchase' => 'Zakup usługi',
    'purchase_form_validated' => 'Wprowadzone dane są prawidłowe. Wybierz teraz metodę płatności.',
    'purchase_success' => 'Usługa została prawidłowo zakupiona.',
    'register_success' =>
        'Konto zostało prawidłowo zarejestrowane. Za chwilę nastąpi automatyczne zalogowanie.',
    'register_vert' => 'Z<br />A<br />R<br />E<br />J<br />E<br />S<br />T<br />R<br />U<br />J',
    'regulations' => 'Regulamin',
    'remove' => 'Usuń',
    'repeat' => 'Powtórz',
    'required_data' => 'Wymagane dane',
    'reset_link_sent' => 'Jeżeli podane dane są prawidłowe, to e-mail wraz z linkiem do zresetowania hasła został wysłany na Twoją skrzynkę pocztową.<br/>
Postępuj zgodnie ze wskazówkami zawartymi w e-mailu.',
    'reset_password' => 'Zresetuj hasło',
    'restore_password_info' => 'W celu odzyskania hasła, podaj swój adres <strong>adres e-mail</strong> lub <strong>nazwę użytkownika</strong>.<br />
W kolejnym etapie, zostanie do Ciebie wysłany e-mail z linkiem do zresetowania hasła.',
    'send' => 'Wyślij',
    'service_no_permission' => 'Nie masz uprawnień, aby móc korzystać z tej usługi.',
    'service_not_displayed' =>
        'Usługa nie może zostać wyświetlona, ponieważ jej moduł nie zapewnia takiej funkcjonalności.',
    'service_takeover_subtitle' => 'Przejęcie usługi służy do przypisania konkretnej, czasowej usługi do swojego konta. Zazwyczaj używane,
	gdy zakupiliśmy usługę nie będąc zalogowani.',
    'service_takeover' => 'Przejęcie usługi',
    'service_taken_over' => 'Usługa została przejęta.',
    'service_was_bought' => 'Zakupiono usługę {1} na serwerze {2}',
    'services_subtitle' => 'Wybierz jedną z poniższych usług, aby przejść do formularza zakupu.',
    'show_more' => 'Pokaż więcej',
    'sign_in_subtitle' => 'Zaloguj się na swoje konto.',
    'sign_up_info' => 'Twoje konto zostało prawidłowo zarejestrowane na adres email: {1}.<br/>
Możesz teraz skorzystać z wielu przydatnych funkcjonalności.',
    'sign_up_subtitle' => 'Załóż konto, aby cieszyć się dodatkowymi korzyściami.',
    'sms_info_bad_code' => 'Wprowadzono błędny kod zwrotny.',
    'sms_info_bad_number' =>
        'Kod jest dobry, lecz niestety został uzyskany poprzez wysłanie SMSa na inny numer.',
    'sms_info_external_error' =>
        'Wystąpił nieznanny błąd po stronie API. Skontaktuj się z właścicielem sklepu.',
    'sms_info_insufficient_data' =>
        'Nieprawidłowo skonfigurowana metoda płatności. Skontaktuj się z właścicielem sklepu.',
    'sms_info_no_connection' => 'Nie można się połączyć ze skryptem weryfikacyjnym.',
    'sms_info_server_error' =>
        'Wystąpił krytyczny błąd po stronie API. Skontaktuj się z właścicielem sklepu.',
    'sms_info_unknown_error' => 'Wystąpił nieznany błąd. Zgłoś go właścicielowi sklepu.',
    'sms_info_wrong_credentials' =>
        'Nieprawidłowo skonfigurowana metoda płatności. Skontaktuj się z właścicielem sklepu.',
    'sms_number' => 'Na numer',
    'sms_send_sms' => 'Wyślij SMSa',
    'sms_text' => 'O treści',
    'sms_unavailable' => 'Nie można dokonać płatności za pomocą SMSa.',
    'steam_id_hint' => 'Wypełniając to pole, zyskujesz możliwość płacenia portfelem w trakcie gry.',
    'steam_id_occupied' => 'Podany SteamID jest już przypisany do innego konta.',
    'take_over' => 'Przejmij',
    'title_payment' => 'Płatność',
    'transfer_cost' => 'Koszt Przelewu',
    'transfer_error' => 'Niestety, ale płatność za pomocą przelewu zakończyła się niepowodzeniem.',
    'transfer_finalized' => 'Transakcja sfinalizowana',
    'transfer_transfer' => 'Przelew',
    'transfer_unavailable' => 'Nie można dokonać płatności za pomocą przelewu.',
    'type_code' => 'Wprowadź kod promocyjny',
    'type_setinfo' => 'Wpisz w konsoli: setinfo _ss "{1}"',
    'use_code' => 'Użyj',
    'user_own_services' => 'Moje obecne usługi',
    'user_own_services_subtitle' => 'Zarządzaj wykupionymi usługami.',
    'wallet_charged' => 'Portfel został doładowany.',
    'wallet_was_charged' => 'Doładowano portfel kwotą {1}',
    'way_of_payment' => 'Methoda płatności',
    'welcome_message' =>
        'Zachęcamy wszystkich do <strong>zarejestrowania się</strong>!<br />W przypadku problemów, skontaktuj się z nami.',
    'welcome_subtitle' => 'Życzymy udanych zakupów.',
    'welcome_title' => 'Witaj w Sklepie SMS!',
    'wrong_id' => 'Błędne ID',
    'wrong_payment_method' => 'Wybrano błędny sposób zapłaty.',
    'wrong_reset_key' => 'Kod resetowania hasła jest błędny.<br />
<a href="{1}">Skontaktuj się</a> z nami w celu uzyskania dodatkowych informacji.',
    'wrong_sender_email' =>
        'E-mail przypisany do Twojego konta jest błędny. Zgłoś to właścicielowi sklepu.',
];
