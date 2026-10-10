<?php

declare(strict_types=1);

$l = &$GLOBALS['TL_LANG']['tl_neo_websitecheck_settings'];

$l['brand_legend'] = 'Wer den Bericht verschickt';
$l['design_legend'] = 'Design';
$l['texts_legend'] = 'Konfigurationen (Texte, E-Mail, PDF je Sprache)';
$l['mail_legend'] = 'E-Mail';
$l['limits_legend'] = 'Grenzen';

$l['brand_name'] = ['Firma / Agentur', 'Steht im Kopf des Berichts, in der E-Mail und in der Signatur. Leer = Titel der Website.'];
$l['owner_name'] = ['Ansprechperson', 'Die Person, die den Bericht unterschreibt, z. B. „Erich Nigg“.'];
$l['phone'] = ['Telefon', 'Steht im Bericht und in der E-Mail-Signatur.'];
$l['website_url'] = ['Website', 'Ihre Website, z. B. https://example.com'];
$l['contact_page'] = ['Kontaktseite', 'Ziel der Knöpfe „Ergebnis besprechen“ in Bericht, E-Mail und Element (das Element kann sie überschreiben).'];
$l['privacy_page'] = ['Datenschutzseite', 'Wird unter dem Kontaktformular verlinkt.'];
$l['logo'] = ['Logo', 'PNG, JPG oder SVG für den Berichtskopf und die E-Mail. Dunkel auf hellem Grund.'];
$l['logo_dark'] = ['Logo für dunklen Hintergrund', 'Optionale Variante für den farbigen E-Mail-Kopf; leer = Logo oben.'];
$l['portrait'] = ['Porträtfoto', 'Optional, erscheint im Kasten „So geht es weiter“ und in der E-Mail-Signatur.'];
$l['color_primary'] = ['Hauptfarbe', 'Überschriften, Kästen, E-Mail-Kopf (Hex, z. B. 2a025f).'];
$l['color_accent'] = ['Akzentfarbe', 'Noten-Badges, Knöpfe, Marker (Hex, z. B. a8f20d).'];
$l['texts_info'] = ['Konfigurationen', ''];
$l['from_name'] = ['Absendername', 'Leer = Firmenname.'];
$l['from_email'] = ['Absenderadresse', 'Leer = config.yaml oder die Contao-Admin-Adresse.'];
$l['reply_to'] = ['Antwortadresse', 'Dorthin gehen Antworten auf die Berichts-E-Mail.'];
$l['notify_email'] = ['Kopie an', 'Bekommt jeden Bericht als Kopie samt Name, E-Mail und Newsletter-Entscheidung des Besuchers. Leer = keine Kopie.'];
$l['checks_per_ip'] = ['Checks je Besucher und Tag', '0 = Wert aus config.yaml (Standard 2).'];
$l['cache_hours'] = ['Zwischenspeicher in Stunden', 'Dieselbe Domain innerhalb dieser Zeit: gespeichertes Ergebnis. 0 = config.yaml (24).'];
$l['retention_days'] = ['Aufbewahrung in Tagen', 'Danach werden Checks samt Kontaktdaten gelöscht. 0 = config.yaml (90).'];
$l['max_pages'] = ['Seiten je Check', 'Startseite plus wichtige Unterseiten, höchstens 8. 0 = config.yaml (5).'];

$l['edit'] = ['Bearbeiten', 'Einstellungen bearbeiten'];
$l['configs'] = ['Konfigurationen', 'E-Mail, PDF und Texte je Sprache gestalten'];
$l['preview_pdf'] = ['Vorschau PDF', 'PDF-Bericht mit dem letzten fertigen Check (oder Beispieldaten) anzeigen'];
$l['preview_mail'] = ['Vorschau E-Mail', 'Berichts-E-Mail mit dem letzten fertigen Check (oder Beispieldaten) anzeigen'];
$l['textsButton'] = 'Konfigurationen bearbeiten (%d)';
$l['textsNone'] = 'Noch keine Konfiguration. Legen Sie je Sprache eine an: Dort gestalten Sie E-Mail und PDF-Bericht, Betreff, Absender und die Texte des Elements. Bis dahin gelten die mitgelieferten Vorlagen.';
$l['textsList'] = 'Vorhanden: %s';
