<?php

declare(strict_types=1);

$l = &$GLOBALS['TL_LANG']['tl_neo_websitecheck_settings'];

$l['brand_legend'] = 'Ki küldi a jelentést';
$l['design_legend'] = 'Dizájn';
$l['texts_legend'] = 'Konfigurációk (szövegek, e-mail, PDF nyelvenként)';
$l['mail_legend'] = 'E-mail';
$l['limits_legend'] = 'Korlátok';

$l['brand_name'] = ['Cég / ügynökség', 'Megjelenik a jelentés fejlécében, az e-mailben és az aláírásban. Üres = a weboldal címe.'];
$l['owner_name'] = ['Kapcsolattartó', 'A jelentést aláíró személy, pl. „Erich Nigg”.'];
$l['phone'] = ['Telefon', 'Megjelenik a jelentésben és az e-mail aláírásában.'];
$l['website_url'] = ['Weboldal', 'Az Ön weboldala, pl. https://example.com'];
$l['contact_page'] = ['Kapcsolati oldal', 'A „Beszéljünk az eredményről” gombok célja a jelentésben, az e-mailben és az elemben (az elem felülírhatja).'];
$l['privacy_page'] = ['Adatvédelmi tájékoztató oldala', 'Az érdeklődői űrlap alatt jelenik meg hivatkozásként.'];
$l['logo'] = ['Logó', 'PNG, JPG vagy SVG a jelentés fejlécéhez és az e-mailhez. Sötét, világos háttéren.'];
$l['logo_dark'] = ['Logó sötét háttérhez', 'Opcionális változat a színes e-mail-fejléchez; üres = a fenti logó.'];
$l['portrait'] = ['Portréfotó', 'Opcionális; a „Mi történik ezután” dobozban és az e-mail aláírásában jelenik meg.'];
$l['color_primary'] = ['Elsődleges szín', 'PDF és e-mail: címsorok, dobozok, e-mail-fejléc (hex, pl. 1f2328). A weboldalon lévő elem megjelenését a téma CSS-e határozza meg (--nwc-* tulajdonságok).'];
$l['color_accent'] = ['Kiemelőszín', 'PDF és e-mail: gombok, kiemelések, elválasztó vonalak (hex, pl. 0969da).'];
$l['texts_info'] = ['Konfigurációk', ''];
$l['from_name'] = ['Feladó neve', 'Üres = cégnév.'];
$l['from_email'] = ['Feladó címe', 'Üres = config.yaml vagy a Contao-adminisztrátor e-mail-címe.'];
$l['reply_to'] = ['Válaszcím', 'Ide érkeznek a jelentés-e-mailre adott válaszok.'];
$l['notify_email'] = ['Másolat ide', 'Minden jelentésről másolatot kap a látogató nevével, e-mail-címével és hírlevél-választásával. Üres = nincs másolat.'];
$l['checks_per_ip_hour'] = ['Ellenőrzések látogatónként óránként', 'Véd a visszaélések és a költségek ellen. 0 = a config.yaml értéke (alapértelmezés: 2).'];
$l['checks_per_ip'] = ['Ellenőrzések látogatónként naponta', '0 = a config.yaml értéke (alapértelmezés: 10).'];
$l['cache_hours'] = ['Gyorsítótár órában', 'Ha ugyanazt a domaint ezen az időn belül újra ellenőrzik: a tárolt eredmény. 0 = config.yaml (24).'];
$l['retention_days'] = ['Megőrzés napokban', 'Az ellenőrzések az érdeklődői adatokkal együtt ennyi idő után törlődnek. 0 = config.yaml (90).'];
$l['max_pages'] = ['Oldalak ellenőrzésenként', 'Kezdőlap és a fontosabb aloldalak, legfeljebb 8. 0 = config.yaml (5).'];

$l['edit'] = ['Szerkesztés', 'Beállítások szerkesztése'];
$l['configs'] = ['Konfigurációk', 'E-mail, PDF és szövegek megtervezése nyelvenként'];
$l['preview_pdf'] = ['PDF-előnézet', 'A PDF-jelentés elkészítése a legutóbbi befejezett ellenőrzéssel (vagy bemutató adatokkal)'];
$l['preview_mail'] = ['E-mail-előnézet', 'A jelentés-e-mail elkészítése a legutóbbi befejezett ellenőrzéssel (vagy bemutató adatokkal)'];
$l['textsButton'] = 'Konfigurációk szerkesztése (%d)';
$l['textsNone'] = 'Még nincs konfiguráció. Hozzon létre nyelvenként egyet: ott tervezheti meg az e-mailt és a PDF-jelentést, a tárgyat, a feladót és az elem szövegeit. Addig a csomagban lévő sablonok érvényesek.';
$l['textsList'] = 'Meglévők: %s';

$l['api_legend'] = 'Kulcsok és MI';
$l['api_info'] = 'Honnan jönnek a kulcsok';
$l['psi_api_key'] = ['PageSpeed-kulcs', 'Google API-kulcs a PageSpeed Insightshoz (betöltési idő mérése). Titkosítva tárolva, többé nem jelenik meg; üresen hagyva a tárolt kulcs marad.'];
$l['ai_api_key'] = ['MI-kulcs', 'Az MI-szolgáltató kulcsa (pl. Requesty) a „látogató szemével” értékeléshez. Titkosítva tárolva; üresen hagyva marad.'];
$l['ai_model'] = ['MI-modell', 'Amint ismert egy MI-kulcs, választás a szolgáltató modelljei közül. Üres = a szerverkonfiguráció értéke.'];
$l['ai_base_url'] = ['Az MI-szolgáltató címe', 'OpenAI-kompatibilis cím, pl. https://router.eu.requesty.ai/v1. Üres = szerverkonfiguráció.'];
$l['apiIntro'] = 'Az itt megadott értékek elsőbbséget élveznek a szerverkonfigurációval (config.yaml / környezet) szemben. Ami itt üres, onnan jön.';
$l['apiPsi'] = 'PageSpeed';
$l['apiAi'] = 'MI';
$l['apiModel'] = 'Használt modell';
$l['apiFromDb'] = 'itt, az adminban tárolva';
$l['apiFromConfig'] = 'a szerverkonfigurációból';
$l['apiMissing'] = 'hiányzik — az ellenőrzés e része kimarad';
$l['modelFromConfig'] = '– szerverkonfiguráció (%s) –';
