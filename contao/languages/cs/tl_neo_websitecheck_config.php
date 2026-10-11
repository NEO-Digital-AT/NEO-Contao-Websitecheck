<?php

declare(strict_types=1);

$l = &$GLOBALS['TL_LANG']['tl_neo_websitecheck_config'];

$l['title_legend'] = 'Konfigurace';
$l['mail_legend'] = 'E-mail návštěvníkovi';
$l['mail_design_legend'] = 'Vzhled e-mailu';
$l['pdf_legend'] = 'PDF zpráva';
$l['pdf_design_legend'] = 'Vzhled PDF zprávy';
$l['owner_legend'] = 'Vaše kopie';
$l['element_legend'] = 'Prvek na stránce';
$l['fe_result'] = ['Výsledek na stránce', 'Stručně: známka, jedna krátká věta a odkaz na PDF. Úplně: navíc skóre, všechny oblasti, opatření a silné stránky. PDF obsahuje vždy vše.'];
$l['fe_result_options'] = ['compact' => 'Stručně (doporučeno)', 'full' => 'Úplně'];
$l['expert_legend'] = 'Interní kopie jako šablona Twig';

$l['title'] = ['Název', 'Interní název, např. „Čeština“ nebo „Angličtina – hotely“.'];
$l['language'] = ['Jazyk', 'Prvek bez výslovného přiřazení použije konfiguraci, jejíž jazyk odpovídá jeho stránce.'];
$l['mail_subject'] = ['Předmět', 'Lze použít zástupné symboly. Výchozí: „Vaše kontrola webu {{domain}}: známka {{grade}} · {{grade_word}}“'];
$l['mail_from_name'] = ['Jméno odesílatele', 'Prázdné = nastavení / název firmy.'];
$l['mail_from_email'] = ['Adresa odesílatele', 'Prázdné = nastavení / config.yaml / adresa administrátora Contao.'];
$l['mail_reply_to'] = ['Adresa pro odpověď', 'Prázdné = nastavení.'];
$l['mail_owner_subject'] = ['Předmět vaší kopie', 'Výchozí: „[Kontrola webu] {{domain}} · známka {{grade}} · {{firstname}}“'];
$l['mail_design'] = ['E-mail', 'Vlevo přidejte bloky, v náhledu na ně klikněte a vpravo je upravte. „Načíst šablonu“ nastaví hotovou strukturu.'];
$l['pdf_filename'] = ['Název PDF souboru', 'Se zástupnými symboly, např. kontrola-webu-{{domain}}-{{date}}.pdf (fungují i {{grade}}, {{firstname}}, {{lastname}}, {{company}}).'];
$l['pdf_design'] = ['PDF zpráva', 'Sestavujte stránku po stránce: titulní strana, datové bloky, vlastní texty a obrázky, zalomení stránky. Náhled používá poslední dokončenou kontrolu nebo ukázková data.'];
$l['mail_body'] = ['E-mail jako kód Twig (pokročilý režim)', 'Jen pro administrátory. Upravuje se v editoru přes „Pokročilé (Twig)“. Je-li vyplněn, použije se tento kód místo vizuálního návrhu.'];
$l['owner_body'] = ['Vaše kopie jako šablona Twig', 'Prázdné = šablona dodaná s modulem.'];
$l['pdf_body'] = ['PDF zpráva jako kód Twig (pokročilý režim)', 'Jen pro administrátory. Upravuje se v editoru přes „Pokročilé (Twig)“. Je-li vyplněn, použije se tento kód místo vizuálního návrhu.'];

$l['fe_url_label'] = ['Popisek nad polem pro adresu', 'Výchozí: „Adresa vašeho webu“'];
$l['fe_submit'] = ['Tlačítko „Zkontrolovat“', ''];
$l['fe_gate_title'] = ['Kontaktní formulář: nadpis', 'Výchozí: „Váš výsledek je hotový“'];
$l['fe_gate_text'] = ['Kontaktní formulář: text', 'Výchozí: „Kam vám ho máme poslat? …“'];
$l['fe_gate_legal'] = ['Kontaktní formulář: informace o ochraně údajů', 'Odkaz na stránku o ochraně osobních údajů se připojí automaticky.'];
$l['fe_newsletter'] = ['Text políčka newsletteru', ''];
$l['fe_gate_submit'] = ['Kontaktní formulář: tlačítko', 'Výchozí: „Zobrazit výsledek“'];
$l['fe_disclaimer'] = ['Výsledek: poznámka', 'Výchozí: „Automatické hodnocení na základě …“'];
$l['fe_cta'] = ['Výsledek: tlačítko', 'Výchozí: „Probrat výsledek – zdarma“'];

$l['new'] = ['Nová konfigurace', 'Přidat konfiguraci pro další jazyk nebo účel'];
$l['edit'] = ['Upravit', 'Upravit konfiguraci ID %s'];
$l['copy'] = ['Duplikovat', 'Duplikovat konfiguraci ID %s'];
$l['delete'] = ['Smazat', 'Smazat konfiguraci ID %s'];
$l['show'] = ['Podrobnosti', 'Zobrazit podrobnosti ID %s'];
$l['preview_pdf'] = ['Náhled PDF', 'Otevřít navrženou zprávu jako PDF'];
$l['preview_mail'] = ['Náhled e-mailu', 'Otevřít navržený e-mail v prohlížeči'];
$l['automatic'] = 'Automaticky (podle jazyka stránky)';
$l['element_config'] = ['Konfigurace', 'Kterou konfiguraci kontroly webu (texty, e-mail, PDF) tento prvek použije. Automaticky = ta, která odpovídá jazyku této stránky.'];

// Multilingual sites: links and newsletter per configuration (= per language); empty = global value.
$l['links_legend'] = 'Stránky tohoto jazyka';
$l['contact_page'] = ['Kontaktní stránka', 'Cíl tlačítka „Probrat výsledek“ ve formuláři, e-mailu a PDF. Prázdné = kontaktní stránka z nastavení. Obsahový prvek ji může přepsat vlastní stránkou pro přesměrování. Bez stránky (zde ani v nastavení) se tlačítko vynechá – modul nikdy sám žádný odkaz nevytváří.'];
$l['privacy_page'] = ['Stránka o ochraně osobních údajů', 'Odkaz v kontaktním kroku formuláře, v e-mailu a v PDF. Prázdné = stránka z nastavení. Bez stránky se žádný odkaz nezobrazí – nastavte ji prosím.'];
$l['newsletter_legend'] = 'Newsletter tohoto jazyka';
$l['nl_list_id'] = ['Seznam v Brevo', 'Seznam pro přihlášky z kontroly v tomto jazyce. Prázdné = seznam z konfiguračního souboru (newsletter.brevo_list_id).'];
$l['nl_template_id'] = ['Šablona dvojitého potvrzení', 'Potvrzovací e-mail v tomto jazyce. Prázdné = šablona z konfiguračního souboru (newsletter.brevo_template_id).'];
$l['nl_redirect_page'] = ['Potvrzovací stránka', 'Stránka po kliknutí na potvrzovací odkaz. Prázdné = newsletter.brevo_redirect_url. Bez potvrzovací stránky není přihlášení k newsletteru možné.'];
$l['nl_list_id_contao'] = ['Kanál newsletteru (Contao)', 'Kanál newsletteru Contao pro přihlášky z kontroly v tomto jazyce.'];
$l['nl_redirect_page_contao'] = ['Potvrzovací stránka', 'Stránka s modulem Contao „Přihlásit odběr“: potvrzovací odkaz v e-mailu vede tam a aktivuje přihlášku. Text e-mailu pochází z tohoto modulu. Bez potvrzovací stránky se k newsletteru nelze přihlásit.'];
$l['ask_contact'] = ['Ptát se na souhlas s kontaktem', 'Zobrazí ve formuláři dobrovolné zaškrtávací pole „Můžete mě k tomuto výsledku jednou kontaktovat e-mailem“. Znění a čas se uloží ke kontrole.'];
$l['fe_contact'] = ['Text pole „jednou kontaktovat“', ''];
