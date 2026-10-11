<?php

declare(strict_types=1);

$l = &$GLOBALS['TL_LANG']['tl_neo_websitecheck_config'];

$l['title_legend'] = 'Konfiguracja';
$l['mail_legend'] = 'E-mail do odwiedzającego';
$l['mail_design_legend'] = 'Wygląd e-maila';
$l['pdf_legend'] = 'Raport PDF';
$l['pdf_design_legend'] = 'Wygląd raportu PDF';
$l['owner_legend'] = 'Kopia dla Państwa';
$l['element_legend'] = 'Element na stronie';
$l['fe_result'] = ['Wynik na stronie', 'Skrócony: ocena, jedno krótkie zdanie i informacja o PDF. Pełny: dodatkowo punkty, wszystkie obszary, działania i mocne strony. PDF zawsze zawiera wszystko.'];
$l['fe_result_options'] = ['compact' => 'Skrócony (zalecane)', 'full' => 'Pełny'];
$l['expert_legend'] = 'Kopia wewnętrzna jako szablon Twig';

$l['title'] = ['Nazwa', 'Nazwa wewnętrzna, np. „Polski” lub „Angielski – hotele”.'];
$l['language'] = ['Język', 'Element bez wyraźnego przypisania używa konfiguracji, której język odpowiada językowi jego strony.'];
$l['mail_subject'] = ['Temat', 'Można używać symboli zastępczych. Domyślnie: „Analiza Państwa strony {{domain}}: ocena {{grade}} · {{grade_word}}”'];
$l['mail_from_name'] = ['Nazwa nadawcy', 'Puste = ustawienia / nazwa firmy.'];
$l['mail_from_email'] = ['Adres nadawcy', 'Puste = ustawienia / config.yaml / adres administratora Contao.'];
$l['mail_reply_to'] = ['Adres do odpowiedzi', 'Puste = ustawienia.'];
$l['mail_owner_subject'] = ['Temat Państwa kopii', 'Domyślnie: „[Analiza strony] {{domain}} · ocena {{grade}} · {{firstname}}”'];
$l['mail_design'] = ['E-mail', 'Bloki dodaje się po lewej, klika w podglądzie i edytuje po prawej. „Wczytaj szablon” ustawia gotową strukturę.'];
$l['pdf_filename'] = ['Nazwa pliku PDF', 'Z symbolami zastępczymi, np. website-check-{{domain}}-{{date}}.pdf (działają też {{grade}}, {{firstname}}, {{lastname}}, {{company}}).'];
$l['pdf_design'] = ['Raport PDF', 'Układ strona po stronie: okładka, bloki danych, własne teksty i obrazy, podziały stron. Podgląd korzysta z ostatniej zakończonej analizy lub z danych demonstracyjnych.'];
$l['mail_body'] = ['E-mail jako kod Twig (tryb zaawansowany)', 'Tylko dla administratorów. Edycja w edytorze przez „Zaawansowane (Twig)”. Jeśli pole jest wypełnione, zamiast wyglądu wizualnego używany jest ten kod.'];
$l['owner_body'] = ['Państwa kopia jako szablon Twig', 'Puste = szablon dołączony do modułu.'];
$l['pdf_body'] = ['Raport PDF jako kod Twig (tryb zaawansowany)', 'Tylko dla administratorów. Edycja w edytorze przez „Zaawansowane (Twig)”. Jeśli pole jest wypełnione, zamiast wyglądu wizualnego używany jest ten kod.'];

$l['fe_url_label'] = ['Etykieta nad polem adresu', 'Domyślnie: „Adres Państwa strony”'];
$l['fe_submit'] = ['Przycisk „Sprawdź teraz”', ''];
$l['fe_gate_title'] = ['Formularz kontaktowy: tytuł', 'Domyślnie: „Państwa wynik jest gotowy”'];
$l['fe_gate_text'] = ['Formularz kontaktowy: tekst', 'Domyślnie: „Dokąd możemy go wysłać? …”'];
$l['fe_gate_legal'] = ['Formularz kontaktowy: informacja o prywatności', 'Link do strony z polityką prywatności jest dodawany automatycznie.'];
$l['fe_newsletter'] = ['Tekst pola wyboru newslettera', ''];
$l['fe_gate_submit'] = ['Formularz kontaktowy: przycisk', 'Domyślnie: „Pokaż wynik”'];
$l['fe_disclaimer'] = ['Wynik: uwaga', 'Domyślnie: „Automatyczna ocena na podstawie …”'];
$l['fe_cta'] = ['Wynik: przycisk', 'Domyślnie: „Omówmy wynik – bezpłatnie”'];

$l['new'] = ['Nowa konfiguracja', 'Dodaj konfigurację dla innego języka lub zastosowania'];
$l['edit'] = ['Edytuj', 'Edytuj konfigurację o ID %s'];
$l['copy'] = ['Duplikuj', 'Duplikuj konfigurację o ID %s'];
$l['delete'] = ['Usuń', 'Usuń konfigurację o ID %s'];
$l['show'] = ['Szczegóły', 'Pokaż szczegóły ID %s'];
$l['preview_pdf'] = ['Podgląd PDF', 'Otwórz zaprojektowany raport jako PDF'];
$l['preview_mail'] = ['Podgląd e-maila', 'Otwórz zaprojektowany e-mail w przeglądarce'];
$l['automatic'] = 'Automatycznie (według języka strony)';
$l['element_config'] = ['Konfiguracja', 'Której konfiguracji analizy strony (teksty, e-mail, PDF) używa ten element. Automatycznie = konfiguracja pasująca do języka tej strony.'];

// Multilingual sites: links and newsletter per configuration (= per language); empty = global value.
$l['links_legend'] = 'Strony w tym języku';
$l['contact_page'] = ['Strona kontaktowa', 'Cel przycisku „Omówmy wynik” w formularzu, e-mailu i PDF. Puste = strona kontaktowa z ustawień. Element treści może to nadpisać własną stroną przekierowania. Bez strony (tutaj lub w ustawieniach) przycisk jest pomijany – moduł nigdy sam nie ustawia linku.'];
$l['privacy_page'] = ['Strona polityki prywatności', 'Link w kroku kontaktowym formularza, w e-mailu i w PDF. Puste = strona polityki prywatności z ustawień. Bez strony link nie jest wyświetlany – prosimy ją ustawić.'];
$l['newsletter_legend'] = 'Newsletter (Brevo) w tym języku';
$l['nl_list_id'] = ['Lista Brevo', 'Lista dla zapisów z analizy w tym języku. Puste = lista z pliku konfiguracyjnego (newsletter.brevo_list_id).'];
$l['nl_template_id'] = ['Szablon podwójnego potwierdzenia', 'E-mail potwierdzający w tym języku. Puste = szablon z pliku konfiguracyjnego (newsletter.brevo_template_id).'];
$l['nl_redirect_page'] = ['Strona potwierdzenia', 'Strona po kliknięciu linku potwierdzającego. Puste = newsletter.brevo_redirect_url. Bez strony potwierdzenia zapis na newsletter nie jest możliwy.'];
