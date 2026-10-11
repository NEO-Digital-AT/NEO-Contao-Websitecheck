<?php

declare(strict_types=1);

$l = &$GLOBALS['TL_LANG']['tl_neo_websitecheck_settings'];

$l['brand_legend'] = 'Tko šalje izvještaj';
$l['design_legend'] = 'Dizajn';
$l['texts_legend'] = 'Konfiguracije (tekstovi, e-mail, PDF po jeziku)';
$l['mail_legend'] = 'E-mail';
$l['limits_legend'] = 'Ograničenja';

$l['brand_name'] = ['Tvrtka / agencija', 'Prikazuje se u zaglavlju izvještaja, u e-mailu i potpisu. Prazno = naslov web-stranice.'];
$l['owner_name'] = ['Kontakt osoba', 'Osoba koja potpisuje izvještaj, npr. „Erich Nigg”.'];
$l['phone'] = ['Telefon', 'Prikazuje se u izvještaju i u potpisu e-maila.'];
$l['website_url'] = ['Web-stranica', 'Vaša web-stranica, npr. https://example.com'];
$l['contact_page'] = ['Kontaktna stranica', 'Odredište gumba „razgovarajmo o rezultatu” u izvještaju, e-mailu i elementu (element ga može nadjačati).'];
$l['privacy_page'] = ['Stranica s pravilima privatnosti', 'Poveznica ispod kontaktnog obrasca.'];
$l['logo'] = ['Logotip', 'PNG, JPG ili SVG za zaglavlje izvještaja i e-mail. Taman na svijetloj pozadini.'];
$l['logo_dark'] = ['Logotip za tamnu pozadinu', 'Neobavezna inačica za obojeno zaglavlje e-maila; prazno = gornji logotip.'];
$l['portrait'] = ['Portretna fotografija', 'Neobavezno; prikazuje se u okviru „što slijedi” i u potpisu e-maila.'];
$l['color_primary'] = ['Primarna boja', 'PDF i e-mail: naslovi, okviri, zaglavlje e-maila (hex, npr. 1f2328). Element na web-stranici oblikuje CSS teme (svojstva --nwc-*).'];
$l['color_accent'] = ['Naglasna boja', 'PDF i e-mail: gumbi, isticanja, linije (hex, npr. 0969da).'];
$l['texts_info'] = ['Konfiguracije', ''];
$l['from_name'] = ['Ime pošiljatelja', 'Prazno = naziv tvrtke.'];
$l['from_email'] = ['Adresa pošiljatelja', 'Prazno = config.yaml ili e-mail Contao administratora.'];
$l['reply_to'] = ['Adresa za odgovor', 'Kamo stižu odgovori na e-mail s izvještajem.'];
$l['notify_email'] = ['Kopija na', 'Prima kopiju svakog izvještaja s imenom posjetitelja, e-mailom i izborom newslettera. Prazno = bez kopije.'];
$l['checks_per_ip_hour'] = ['Provjera po posjetitelju na sat', 'Štiti od zlouporabe i troškova. 0 = vrijednost iz config.yaml (zadano 2).'];
$l['checks_per_ip'] = ['Provjera po posjetitelju na dan', '0 = vrijednost iz config.yaml (zadano 10).'];
$l['cache_hours'] = ['Predmemorija u satima', 'Ista domena ponovno unutar tog vremena: spremljeni rezultat. 0 = config.yaml (24).'];
$l['retention_days'] = ['Čuvanje u danima', 'Provjere zajedno s podacima o kontaktu brišu se nakon tog roka. 0 = config.yaml (90).'];
$l['max_pages'] = ['Stranica po provjeri', 'Početna stranica i važne podstranice, najviše 8. 0 = config.yaml (5).'];

$l['edit'] = ['Uredi', 'Uredi postavke'];
$l['configs'] = ['Konfiguracije', 'Oblikujte e-mail, PDF i tekstove po jeziku'];
$l['preview_pdf'] = ['Pregled PDF-a', 'Izradi PDF izvještaj s posljednjom dovršenom provjerom (ili demo podacima)'];
$l['preview_mail'] = ['Pregled e-maila', 'Izradi e-mail s izvještajem s posljednjom dovršenom provjerom (ili demo podacima)'];
$l['textsButton'] = 'Uredi konfiguracije (%d)';
$l['textsNone'] = 'Još nema konfiguracije. Izradite jednu po jeziku: ondje oblikujete e-mail i PDF izvještaj, predmet, pošiljatelja i tekstove elementa. Do tada vrijede ugrađeni predlošci.';
$l['textsList'] = 'Postojeće: %s';

$l['api_legend'] = 'Ključevi i UI';
$l['api_info'] = 'Odakle dolaze ključevi';
$l['psi_api_key'] = ['PageSpeed ključ', 'Google API ključ za PageSpeed Insights (mjerenje brzine učitavanja). Sprema se šifrirano i više se ne prikazuje; prazno polje = spremljeni ključ ostaje.'];
$l['ai_api_key'] = ['UI ključ', 'Ključ pružatelja UI (npr. Requesty) za ocjenu „očima posjetitelja”. Sprema se šifrirano; prazno polje = ostaje.'];
$l['ai_model'] = ['UI model', 'Čim je UI ključ poznat, izbor među modelima pružatelja. Prazno = vrijednost iz konfiguracije poslužitelja.'];
$l['ai_base_url'] = ['Adresa pružatelja UI', 'Adresa kompatibilna s OpenAI, npr. https://router.eu.requesty.ai/v1. Prazno = konfiguracija poslužitelja.'];
$l['apiIntro'] = 'Ovdje unesene vrijednosti imaju prednost pred konfiguracijom poslužitelja (config.yaml / okruženje). Što je ovdje prazno, dolazi odande.';
$l['apiPsi'] = 'PageSpeed';
$l['apiAi'] = 'UI';
$l['apiModel'] = 'Model u upotrebi';
$l['apiFromDb'] = 'spremljeno ovdje u administraciji';
$l['apiFromConfig'] = 'iz konfiguracije poslužitelja';
$l['apiMissing'] = 'nedostaje — ovaj dio provjere se preskače';
$l['modelFromConfig'] = '– konfiguracija poslužitelja (%s) –';
