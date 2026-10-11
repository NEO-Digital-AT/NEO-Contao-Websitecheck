<?php

declare(strict_types=1);

$l = &$GLOBALS['TL_LANG']['tl_neo_websitecheck_settings'];

$l['brand_legend'] = 'Kdo pošlje poročilo';
$l['design_legend'] = 'Oblikovanje';
$l['texts_legend'] = 'Konfiguracije (besedila, e-pošta, PDF po jezikih)';
$l['mail_legend'] = 'E-pošta';
$l['limits_legend'] = 'Omejitve';

$l['brand_name'] = ['Podjetje / agencija', 'Prikazano v glavi poročila, v e-pošti in podpisu. Prazno = naslov spletnega mesta.'];
$l['owner_name'] = ['Kontaktna oseba', 'Oseba, ki podpiše poročilo, npr. „Erich Nigg“.'];
$l['phone'] = ['Telefon', 'Prikazano v poročilu in v podpisu e-pošte.'];
$l['website_url'] = ['Spletna stran', 'Vaša spletna stran, npr. https://example.com'];
$l['contact_page'] = ['Kontaktna stran', 'Cilj gumbov „pogovorimo se o rezultatu“ v poročilu, e-pošti in elementu (element jo lahko preglasi).'];
$l['privacy_page'] = ['Stran s politiko zasebnosti', 'Povezava pod obrazcem za kontaktne podatke.'];
$l['logo'] = ['Logotip', 'PNG, JPG ali SVG za glavo poročila in e-pošto. Temen na svetlem ozadju.'];
$l['logo_dark'] = ['Logotip za temno ozadje', 'Neobvezna različica za barvno glavo e-pošte; prazno = zgornji logotip.'];
$l['portrait'] = ['Portretna fotografija', 'Neobvezno, prikazano v polju „kako naprej“ in v podpisu e-pošte.'];
$l['color_primary'] = ['Primarna barva', 'PDF in e-pošta: naslovi, polja, glava e-pošte (hex, npr. 1f2328). Element na spletni strani oblikuje CSS teme (lastnosti --nwc-*).'];
$l['color_accent'] = ['Poudarna barva', 'PDF in e-pošta: gumbi, poudarki, črte (hex, npr. 0969da).'];
$l['texts_info'] = ['Konfiguracije', ''];
$l['from_name'] = ['Ime pošiljatelja', 'Prazno = ime podjetja.'];
$l['from_email'] = ['Naslov pošiljatelja', 'Prazno = config.yaml ali skrbniški e-poštni naslov Contao.'];
$l['reply_to'] = ['Naslov za odgovor', 'Kam gredo odgovori na e-pošto s poročilom.'];
$l['notify_email'] = ['Kopija na', 'Prejme kopijo vsakega poročila z imenom, e-poštnim naslovom in izbiro e-novic obiskovalca. Prazno = brez kopije.'];
$l['checks_per_ip_hour'] = ['Preverjanja na obiskovalca na uro', 'Ščiti pred zlorabo in stroški. 0 = vrednost iz config.yaml (privzeto 2).'];
$l['checks_per_ip'] = ['Preverjanja na obiskovalca na dan', '0 = vrednost iz config.yaml (privzeto 10).'];
$l['cache_hours'] = ['Predpomnilnik v urah', 'Ista domena znova v tem času: shranjeni rezultat. 0 = config.yaml (24).'];
$l['retention_days'] = ['Hramba v dneh', 'Preverjanja vključno s podatki o stikih se po tem času izbrišejo. 0 = config.yaml (90).'];
$l['max_pages'] = ['Strani na preverjanje', 'Začetna stran in ključne podstrani, največ 8. 0 = config.yaml (5).'];

$l['edit'] = ['Uredi', 'Uredi nastavitve'];
$l['configs'] = ['Konfiguracije', 'Oblikujte e-pošto, PDF in besedila po jezikih'];
$l['preview_pdf'] = ['Predogled PDF', 'Ustvari poročilo PDF z zadnjim dokončanim preverjanjem (ali predstavitvenimi podatki)'];
$l['preview_mail'] = ['Predogled e-pošte', 'Ustvari e-pošto s poročilom z zadnjim dokončanim preverjanjem (ali predstavitvenimi podatki)'];
$l['textsButton'] = 'Uredi konfiguracije (%d)';
$l['textsNone'] = 'Konfiguracije še ni. Ustvarite eno za vsak jezik: tam oblikujete e-pošto in poročilo PDF, zadevo, pošiljatelja in besedila elementa. Do takrat veljajo priložene predloge.';
$l['textsList'] = 'Obstoječe: %s';
