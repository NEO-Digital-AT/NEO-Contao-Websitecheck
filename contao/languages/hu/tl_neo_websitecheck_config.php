<?php

declare(strict_types=1);

$l = &$GLOBALS['TL_LANG']['tl_neo_websitecheck_config'];

$l['title_legend'] = 'Konfiguráció';
$l['mail_legend'] = 'E-mail a látogatónak';
$l['mail_design_legend'] = 'Az e-mail megtervezése';
$l['pdf_legend'] = 'PDF-jelentés';
$l['pdf_design_legend'] = 'A PDF-jelentés megtervezése';
$l['owner_legend'] = 'Az Ön másolata';
$l['element_legend'] = 'Az elem az oldalon';
$l['fe_result'] = ['Eredmény az oldalon', 'Rövid: osztályzat, egy rövid mondat és utalás a PDF-re. Teljes: ezen felül pontszámok, minden terület, teendők és erősségek. A PDF mindig mindent tartalmaz.'];
$l['fe_result_options'] = ['compact' => 'Rövid (ajánlott)', 'full' => 'Teljes'];
$l['expert_legend'] = 'Belső másolat Twig-sablonként';

$l['title'] = ['Név', 'Belső név, pl. „Német” vagy „Angol – szállodák”.'];
$l['language'] = ['Nyelv', 'Az olyan elem, amelyhez nincs kifejezetten konfiguráció rendelve, azt a konfigurációt használja, amelynek nyelve megegyezik az oldaléval.'];
$l['mail_subject'] = ['Tárgy', 'Helyőrzők használhatók. Alapértelmezés: „Az Ön weboldal-ellenőrzése ({{domain}}): osztályzat {{grade}} · {{grade_word}}”'];
$l['mail_from_name'] = ['Feladó neve', 'Üres = beállítások / cégnév.'];
$l['mail_from_email'] = ['Feladó címe', 'Üres = beállítások / config.yaml / Contao-adminisztrátor címe.'];
$l['mail_reply_to'] = ['Válaszcím', 'Üres = beállítások.'];
$l['mail_owner_subject'] = ['Az Ön másolatának tárgya', 'Alapértelmezés: „[Weboldal-ellenőrzés] {{domain}} · osztályzat {{grade}} · {{firstname}}”'];
$l['mail_design'] = ['E-mail', 'Bal oldalon adjon hozzá blokkokat, kattintson rájuk az előnézetben, és jobb oldalon szerkessze őket. A „Sablon betöltése” kész szerkezetet állít be.'];
$l['pdf_filename'] = ['PDF-fájlnév', 'Helyőrzőkkel, pl. website-check-{{domain}}-{{date}}.pdf (a {{grade}}, {{firstname}}, {{lastname}}, {{company}} is működik).'];
$l['pdf_design'] = ['PDF-jelentés', 'Oldalanként állítsa össze: borító, adatblokkok, saját szövegek és képek, oldaltörések. Az előnézet a legutóbbi befejezett ellenőrzést vagy bemutató adatokat használ.'];
$l['mail_body'] = ['E-mail Twig-kódként (haladó mód)', 'Csak adminisztrátoroknak. A tervezőben a „Haladó (Twig)” gombbal szerkeszthető. Ha ki van töltve, a vizuális terv helyett ez a kód lesz használva.'];
$l['owner_body'] = ['Az Ön másolata Twig-sablonként', 'Üres = a csomagban lévő sablon.'];
$l['pdf_body'] = ['PDF-jelentés Twig-kódként (haladó mód)', 'Csak adminisztrátoroknak. A tervezőben a „Haladó (Twig)” gombbal szerkeszthető. Ha ki van töltve, a vizuális terv helyett ez a kód lesz használva.'];

$l['fe_url_label'] = ['Felirat a címmező felett', 'Alapértelmezés: „Az Ön weboldalának címe”'];
$l['fe_submit'] = ['„Ellenőrzés most” gomb', ''];
$l['fe_gate_title'] = ['Kapcsolatfelvételi űrlap: cím', 'Alapértelmezés: „Elkészült az eredménye”'];
$l['fe_gate_text'] = ['Kapcsolatfelvételi űrlap: szöveg', 'Alapértelmezés: „Hová küldhetjük? …”'];
$l['fe_gate_legal'] = ['Kapcsolatfelvételi űrlap: adatvédelmi tájékoztatás', 'Az adatvédelmi oldalra mutató hivatkozás automatikusan hozzáadódik.'];
$l['fe_newsletter'] = ['A hírlevél jelölőnégyzet szövege', ''];
$l['fe_gate_submit'] = ['Kapcsolatfelvételi űrlap: gomb', 'Alapértelmezés: „Eredmény megjelenítése”'];
$l['fe_disclaimer'] = ['Eredmény: megjegyzés', 'Alapértelmezés: „Automatikus értékelés a következők alapján …”'];
$l['fe_cta'] = ['Eredmény: gomb', 'Alapértelmezés: „Beszéljük meg az eredményt – díjmentesen”'];

$l['new'] = ['Új konfiguráció', 'Konfiguráció hozzáadása egy másik nyelvhez vagy felhasználási célhoz'];
$l['edit'] = ['Szerkesztés', 'A(z) %s azonosítójú konfiguráció szerkesztése'];
$l['copy'] = ['Másolat készítése', 'A(z) %s azonosítójú konfiguráció másolása'];
$l['delete'] = ['Törlés', 'A(z) %s azonosítójú konfiguráció törlése'];
$l['show'] = ['Részletek', 'A(z) %s azonosító részleteinek megjelenítése'];
$l['preview_pdf'] = ['PDF-előnézet', 'A megtervezett jelentés megnyitása PDF-ként'];
$l['preview_mail'] = ['E-mail-előnézet', 'A megtervezett e-mail megnyitása a böngészőben'];
$l['automatic'] = 'Automatikus (az oldal nyelve szerint)';
$l['element_config'] = ['Konfiguráció', 'Melyik weboldal-ellenőrzési konfigurációt (szövegek, e-mail, PDF) használja ez az elem. Automatikus = az oldal nyelvének megfelelő.'];

// Multilingual sites: links and newsletter per configuration (= per language); empty = global value.
$l['links_legend'] = 'Az adott nyelv oldalai';
$l['contact_page'] = ['Kapcsolati oldal', 'A „Beszéljük meg az eredményt” célja az űrlapon, az e-mailben és a PDF-ben. Üres = a beállításokban megadott kapcsolati oldal. A tartalmi elem saját továbbítási oldallal felülírhatja. Oldal nélkül (itt vagy a beállításokban) a gomb elmarad – a csomag soha nem állít be magától hivatkozást.'];
$l['privacy_page'] = ['Adatvédelmi oldal', 'Hivatkozás az űrlap kapcsolatfelvételi lépésében, az e-mailben és a PDF-ben. Üres = a beállításokban megadott adatvédelmi oldal. Oldal nélkül nem jelenik meg hivatkozás – kérjük, állítsa be.'];
$l['newsletter_legend'] = 'Az adott nyelv hírlevele';
$l['nl_list_id'] = ['Brevo-lista', 'Lista az ezen a nyelven, az ellenőrzésből érkező feliratkozásokhoz. Üres = a konfigurációs fájlban megadott lista (newsletter.brevo_list_id).'];
$l['nl_template_id'] = ['Dupla megerősítés sablonja', 'Megerősítő e-mail ezen a nyelven. Üres = a konfigurációs fájlban megadott sablon (newsletter.brevo_template_id).'];
$l['nl_redirect_page'] = ['Megerősítő oldal', 'A megerősítő hivatkozásra kattintás után megjelenő oldal. Üres = newsletter.brevo_redirect_url. Megerősítő oldal nélkül nincs hírlevél-feliratkozás.'];
$l['nl_list_id_contao'] = ['Hírlevélcsatorna (Contao)', 'Contao-hírlevélcsatorna az ellenőrzésből érkező feliratkozásokhoz ezen a nyelven.'];
$l['nl_redirect_page_contao'] = ['Megerősítő oldal', 'Oldal a Contao „Feliratkozás” moduljával: az e-mailben lévő megerősítő link oda vezet, és aktiválja a feliratkozást. Az e-mail szövege ebből a modulból származik. Megerősítő oldal nélkül nincs hírlevél-feliratkozás.'];
