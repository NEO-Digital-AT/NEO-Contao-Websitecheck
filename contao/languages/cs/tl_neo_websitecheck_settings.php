<?php

declare(strict_types=1);

$l = &$GLOBALS['TL_LANG']['tl_neo_websitecheck_settings'];

$l['brand_legend'] = 'Kdo zprávu odesílá';
$l['design_legend'] = 'Vzhled';
$l['texts_legend'] = 'Konfigurace (texty, e-mail, PDF pro každý jazyk)';
$l['mail_legend'] = 'E-mail';
$l['limits_legend'] = 'Limity';

$l['brand_name'] = ['Firma / agentura', 'Zobrazí se v záhlaví zprávy, v e-mailu a v podpisu. Prázdné = název webu.'];
$l['owner_name'] = ['Kontaktní osoba', 'Osoba, která zprávu podepisuje, např. „Erich Nigg“.'];
$l['phone'] = ['Telefon', 'Zobrazí se ve zprávě a v podpisu e-mailu.'];
$l['website_url'] = ['Web', 'Váš web, např. https://example.com'];
$l['contact_page'] = ['Kontaktní stránka', 'Cíl tlačítek „probrat výsledek“ ve zprávě, v e-mailu a v prvku (prvek ji může přepsat).'];
$l['privacy_page'] = ['Stránka o ochraně osobních údajů', 'Odkaz pod kontaktním formulářem.'];
$l['logo'] = ['Logo', 'PNG, JPG nebo SVG pro záhlaví zprávy a e-mail. Tmavé na světlém pozadí.'];
$l['logo_dark'] = ['Logo pro tmavé pozadí', 'Volitelná varianta pro barevné záhlaví e-mailu; prázdné = logo výše.'];
$l['portrait'] = ['Portrétní fotografie', 'Volitelná, zobrazí se v rámečku „co bude dál“ a v podpisu e-mailu.'];
$l['color_primary'] = ['Hlavní barva', 'PDF a e-mail: nadpisy, rámečky, záhlaví e-mailu (hex, např. 1f2328). Vzhled prvku na webu určuje CSS šablony (vlastnosti --nwc-*).'];
$l['color_accent'] = ['Zvýrazňující barva', 'PDF a e-mail: tlačítka, zvýraznění, linky (hex, např. 0969da).'];
$l['texts_info'] = ['Konfigurace', ''];
$l['from_name'] = ['Jméno odesílatele', 'Prázdné = název firmy.'];
$l['from_email'] = ['Adresa odesílatele', 'Prázdné = config.yaml nebo e-mail administrátora Contao.'];
$l['reply_to'] = ['Adresa pro odpověď', 'Kam chodí odpovědi na e-mail se zprávou.'];
$l['notify_email'] = ['Kopie na', 'Dostává kopii každé zprávy se jménem a e-mailem návštěvníka a jeho volbou newsletteru. Prázdné = bez kopie.'];
$l['checks_per_ip_hour'] = ['Kontrol na návštěvníka za hodinu', 'Chrání před zneužitím a náklady. 0 = hodnota z config.yaml (výchozí 2).'];
$l['checks_per_ip'] = ['Kontrol na návštěvníka za den', '0 = hodnota z config.yaml (výchozí 10).'];
$l['cache_hours'] = ['Mezipaměť v hodinách', 'Stejná doména znovu během této doby: použije se uložený výsledek. 0 = config.yaml (24).'];
$l['retention_days'] = ['Doba uchování ve dnech', 'Kontroly včetně kontaktních údajů se poté smažou. 0 = config.yaml (90).'];
$l['max_pages'] = ['Stránek na kontrolu', 'Úvodní stránka plus hlavní podstránky, nejvýše 8. 0 = config.yaml (5).'];

$l['edit'] = ['Upravit', 'Upravit nastavení'];
$l['configs'] = ['Konfigurace', 'Vzhled e-mailu, PDF a texty pro každý jazyk'];
$l['preview_pdf'] = ['Náhled PDF', 'Vytvořit PDF zprávu s poslední dokončenou kontrolou (nebo ukázkovými daty)'];
$l['preview_mail'] = ['Náhled e-mailu', 'Vytvořit e-mail se zprávou s poslední dokončenou kontrolou (nebo ukázkovými daty)'];
$l['textsButton'] = 'Upravit konfigurace (%d)';
$l['textsNone'] = 'Zatím žádná konfigurace. Vytvořte jednu pro každý jazyk: tam navrhnete e-mail a PDF zprávu, předmět, odesílatele a texty prvku. Do té doby platí šablony dodané s modulem.';
$l['textsList'] = 'Existující: %s';

$l['api_legend'] = 'Klíče a AI';
$l['api_info'] = 'Odkud klíče pocházejí';
$l['psi_api_key'] = ['Klíč PageSpeed', 'Klíč Google API pro PageSpeed Insights (měření rychlosti načítání). Ukládá se šifrovaně a už se nezobrazí; prázdné pole = uložený klíč zůstane.'];
$l['ai_api_key'] = ['Klíč AI', 'Klíč poskytovatele AI (např. Requesty) pro hodnocení „očima návštěvníka“. Ukládá se šifrovaně; prázdné pole = zůstane.'];
$l['ai_model'] = ['Model AI', 'Jakmile je klíč AI znám, výběr z modelů poskytovatele. Prázdné = hodnota z konfigurace serveru.'];
$l['ai_base_url'] = ['Adresa poskytovatele AI', 'Adresa kompatibilní s OpenAI, např. https://router.eu.requesty.ai/v1. Prázdné = konfigurace serveru.'];
$l['apiIntro'] = 'Hodnoty zadané zde mají přednost před konfigurací serveru (config.yaml / prostředí). Co je zde prázdné, přichází odtamtud.';
$l['apiPsi'] = 'PageSpeed';
$l['apiAi'] = 'AI';
$l['apiModel'] = 'Použitý model';
$l['apiFromDb'] = 'uloženo zde v administraci';
$l['apiFromConfig'] = 'z konfigurace serveru';
$l['apiMissing'] = 'chybí — tato část kontroly se vynechá';
$l['modelFromConfig'] = '– konfigurace serveru (%s) –';
