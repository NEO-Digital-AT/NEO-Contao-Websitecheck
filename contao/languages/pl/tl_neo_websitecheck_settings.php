<?php

declare(strict_types=1);

$l = &$GLOBALS['TL_LANG']['tl_neo_websitecheck_settings'];

$l['brand_legend'] = 'Kto wysyła raport';
$l['design_legend'] = 'Wygląd';
$l['texts_legend'] = 'Konfiguracje (teksty, e-mail, PDF dla każdego języka)';
$l['mail_legend'] = 'E-mail';
$l['limits_legend'] = 'Limity';

$l['brand_name'] = ['Firma / agencja', 'Wyświetlana w nagłówku raportu, w e-mailu i w podpisie. Puste = tytuł witryny.'];
$l['owner_name'] = ['Osoba kontaktowa', 'Osoba, która podpisuje raport, np. „Jan Kowalski”.'];
$l['phone'] = ['Telefon', 'Wyświetlany w raporcie i w podpisie e-maila.'];
$l['website_url'] = ['Strona internetowa', 'Państwa strona, np. https://example.com'];
$l['contact_page'] = ['Strona kontaktowa', 'Cel przycisków „Omówmy wynik” w raporcie, w e-mailu i w elemencie (element może to nadpisać).'];
$l['privacy_page'] = ['Strona polityki prywatności', 'Link pod formularzem kontaktowym.'];
$l['logo'] = ['Logo', 'PNG, JPG lub SVG do nagłówka raportu i e-maila. Ciemne na jasnym tle.'];
$l['logo_dark'] = ['Logo na ciemne tło', 'Opcjonalny wariant dla kolorowego nagłówka e-maila; puste = logo powyżej.'];
$l['portrait'] = ['Zdjęcie portretowe', 'Opcjonalne, wyświetlane w ramce „Co dalej” i w podpisie e-maila.'];
$l['color_primary'] = ['Kolor główny', 'PDF i e-mail: nagłówki, ramki, nagłówek e-maila (hex, np. 1f2328). Wygląd elementu na stronie określa CSS motywu (właściwości --nwc-*).'];
$l['color_accent'] = ['Kolor akcentu', 'PDF i e-mail: przyciski, wyróżnienia, linie (hex, np. 0969da).'];
$l['texts_info'] = ['Konfiguracje', ''];
$l['from_name'] = ['Nazwa nadawcy', 'Puste = nazwa firmy.'];
$l['from_email'] = ['Adres nadawcy', 'Puste = config.yaml lub adres e-mail administratora Contao.'];
$l['reply_to'] = ['Adres do odpowiedzi', 'Dokąd trafiają odpowiedzi na e-mail z raportem.'];
$l['notify_email'] = ['Kopia do', 'Otrzymuje kopię każdego raportu z imieniem, adresem e-mail i decyzją odwiedzającego w sprawie newslettera. Puste = bez kopii.'];
$l['checks_per_ip_hour'] = ['Analizy na odwiedzającego na godzinę', 'Chroni przed nadużyciami i kosztami. 0 = wartość z config.yaml (domyślnie 2).'];
$l['checks_per_ip'] = ['Analizy na odwiedzającego na dzień', '0 = wartość z config.yaml (domyślnie 10).'];
$l['cache_hours'] = ['Pamięć podręczna w godzinach', 'Ta sama domena ponownie w tym czasie: zapisany wynik. 0 = config.yaml (24).'];
$l['retention_days'] = ['Okres przechowywania w dniach', 'Po tym czasie analizy wraz z danymi kontaktowymi są usuwane. 0 = config.yaml (90).'];
$l['max_pages'] = ['Liczba stron na analizę', 'Strona startowa plus najważniejsze podstrony, maksymalnie 8. 0 = config.yaml (5).'];

$l['edit'] = ['Edytuj', 'Edytuj ustawienia'];
$l['configs'] = ['Konfiguracje', 'Wygląd e-maila, PDF i teksty dla każdego języka'];
$l['preview_pdf'] = ['Podgląd PDF', 'Wygeneruj raport PDF na podstawie ostatniej zakończonej analizy (lub danych demonstracyjnych)'];
$l['preview_mail'] = ['Podgląd e-maila', 'Wygeneruj e-mail z raportem na podstawie ostatniej zakończonej analizy (lub danych demonstracyjnych)'];
$l['textsButton'] = 'Edytuj konfiguracje (%d)';
$l['textsNone'] = 'Brak konfiguracji. Należy utworzyć jedną dla każdego języka: tam projektuje się e-mail i raport PDF, temat, nadawcę oraz teksty elementu. Do tego czasu obowiązują szablony dołączone do modułu.';
$l['textsList'] = 'Istniejące: %s';

$l['api_legend'] = 'Klucze i AI';
$l['api_info'] = 'Skąd pochodzą klucze';
$l['psi_api_key'] = ['Klucz PageSpeed', 'Klucz API Google do PageSpeed Insights (pomiar czasu ładowania). Zapisywany zaszyfrowany i nigdy więcej nie wyświetlany; puste pole = zapisany klucz zostaje.'];
$l['ai_api_key'] = ['Klucz AI', 'Klucz dostawcy AI (np. Requesty) do oceny „oczami odwiedzającego”. Zapisywany zaszyfrowany; puste pole = zostaje.'];
$l['ai_model'] = ['Model AI', 'Gdy klucz AI jest znany, wybór spośród modeli dostawcy. Puste = wartość z konfiguracji serwera.'];
$l['ai_base_url'] = ['Adres dostawcy AI', 'Adres zgodny z OpenAI, np. https://router.eu.requesty.ai/v1. Puste = konfiguracja serwera.'];
$l['apiIntro'] = 'Wartości wpisane tutaj mają pierwszeństwo przed konfiguracją serwera (config.yaml / środowisko). To, co tu jest puste, pochodzi stamtąd.';
$l['apiPsi'] = 'PageSpeed';
$l['apiAi'] = 'AI';
$l['apiModel'] = 'Używany model';
$l['apiFromDb'] = 'zapisany tutaj w panelu';
$l['apiFromConfig'] = 'z konfiguracji serwera';
$l['apiMissing'] = 'brak — ta część checku zostaje pominięta';
$l['modelFromConfig'] = '– konfiguracja serwera (%s) –';
$l['test_api'] = ['Sprawdź połączenia', ''];
$l['checks_total_hour'] = ['Nowe checki łącznie na godzinę', 'Wszyscy odwiedzający razem; chroni budżet AI przed automatycznym użyciem. Wyniki z pamięci podręcznej się nie liczą. 0 = config.yaml (20).'];
$l['checks_total_day'] = ['Nowe checki łącznie na dzień', 'Wszyscy odwiedzający razem. 0 = config.yaml (60).'];
