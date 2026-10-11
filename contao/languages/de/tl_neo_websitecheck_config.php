<?php

declare(strict_types=1);

$l = &$GLOBALS['TL_LANG']['tl_neo_websitecheck_config'];

$l['title_legend'] = 'Konfiguration';
$l['mail_legend'] = 'E-Mail an den Besucher';
$l['mail_design_legend'] = 'E-Mail gestalten';
$l['pdf_legend'] = 'PDF-Bericht';
$l['pdf_design_legend'] = 'PDF-Bericht gestalten';
$l['owner_legend'] = 'Ihre Kopie';
$l['element_legend'] = 'Das Element auf der Seite';
$l['fe_result'] = ['Ergebnis auf der Seite', 'Kurzfassung: Note, ein kurzer Satz und der Hinweis auf das PDF. Ausführlich: zusätzlich Messwerte, alle Bereiche, Maßnahmen und Stärken. Das PDF enthält immer alles.'];
$l['fe_result_options'] = ['compact' => 'Kurzfassung (empfohlen)', 'full' => 'Ausführlich'];
$l['expert_legend'] = 'Interne Kopie als Twig-Vorlage';

$l['title'] = ['Bezeichnung', 'Interner Name, z. B. „Deutsch“ oder „Englisch – Hotels“.'];
$l['language'] = ['Sprache', 'Ein Element ohne ausdrückliche Zuordnung nimmt die Konfiguration mit der Sprache seiner Seite.'];
$l['mail_subject'] = ['Betreff', 'Platzhalter erlaubt. Standard: „Ihr Website-Check für {{domain}}: Note {{grade}} · {{grade_word}}“'];
$l['mail_from_name'] = ['Absendername', 'Leer = Einstellungen / Firmenname.'];
$l['mail_from_email'] = ['Absenderadresse', 'Leer = Einstellungen / config.yaml / Contao-Admin-Adresse.'];
$l['mail_reply_to'] = ['Antwortadresse', 'Leer = Einstellungen.'];
$l['mail_owner_subject'] = ['Betreff Ihrer Kopie', 'Standard: „[Website-Check] {{domain}} · Note {{grade}} · {{firstname}}“'];
$l['mail_design'] = ['E-Mail', 'Blöcke links hinzufügen, in der Vorschau anklicken und rechts bearbeiten. „Vorlage laden“ setzt einen fertigen Aufbau.'];
$l['pdf_filename'] = ['PDF-Dateiname', 'Mit Platzhaltern, z. B. website-check-{{domain}}-{{date}}.pdf ({{grade}}, {{firstname}}, {{lastname}}, {{company}} gehen auch).'];
$l['pdf_design'] = ['PDF-Bericht', 'Seite für Seite zusammenstellen: Deckblatt, Datenblöcke, eigene Texte und Bilder, Seitenumbrüche. Die Vorschau zeigt den letzten fertigen Check oder Demo-Daten.'];
$l['mail_body'] = ['E-Mail als Twig-Code (erweiterter Modus)', 'Nur für Administratoren. Wird im Designer über „Erweitert (Twig)“ bearbeitet. Wenn ausgefüllt, gilt dieser Code statt der visuellen Gestaltung.'];
$l['owner_body'] = ['Ihre Kopie als Twig-Vorlage', 'Leer = mitgelieferte Vorlage.'];
$l['pdf_body'] = ['PDF-Bericht als Twig-Code (erweiterter Modus)', 'Nur für Administratoren. Wird im Designer über „Erweitert (Twig)“ bearbeitet. Wenn ausgefüllt, gilt dieser Code statt der visuellen Gestaltung.'];

$l['fe_url_label'] = ['Beschriftung über dem Adressfeld', 'Standard: „Adresse Ihrer Website“'];
$l['fe_submit'] = ['Knopf „Jetzt prüfen“', ''];
$l['fe_gate_title'] = ['Kontaktformular: Titel', 'Standard: „Ihr Ergebnis ist fertig“'];
$l['fe_gate_text'] = ['Kontaktformular: Text', 'Standard: „Wohin dürfen wir es schicken? …“'];
$l['fe_gate_legal'] = ['Kontaktformular: Datenschutzhinweis', 'Der Link zur Datenschutzseite wird automatisch angehängt.'];
$l['fe_newsletter'] = ['Text des Newsletter-Häkchens', ''];
$l['fe_gate_submit'] = ['Kontaktformular: Knopf', 'Standard: „Ergebnis ansehen“'];
$l['fe_disclaimer'] = ['Ergebnis: Hinweis', 'Standard: „Automatische Einschätzung auf Basis von …“'];
$l['fe_cta'] = ['Ergebnis: Knopf', 'Standard: „Ergebnis besprechen, kostenlos“'];

$l['new'] = ['Neue Konfiguration', 'Eine Konfiguration für eine weitere Sprache oder einen anderen Einsatz anlegen'];
$l['edit'] = ['Bearbeiten', 'Konfiguration ID %s bearbeiten'];
$l['copy'] = ['Duplizieren', 'Konfiguration ID %s duplizieren'];
$l['delete'] = ['Löschen', 'Konfiguration ID %s löschen'];
$l['show'] = ['Details', 'Details von ID %s anzeigen'];
$l['preview_pdf'] = ['PDF-Vorschau', 'Den gestalteten Bericht als PDF öffnen'];
$l['preview_mail'] = ['E-Mail-Vorschau', 'Die gestaltete E-Mail im Browser öffnen'];
$l['automatic'] = 'Automatisch (nach Seitensprache)';
$l['element_config'] = ['Konfiguration', 'Welche Website-Check-Konfiguration (Texte, E-Mail, PDF) dieses Element verwendet. Automatisch = die mit der Sprache dieser Seite.'];

// Mehrsprachigkeit: Links und Newsletter je Konfiguration (= je Sprache); leer = globaler Wert.
$l['links_legend'] = 'Seiten dieser Sprache';
$l['contact_page'] = ['Kontaktseite', 'Ziel von „Ergebnis besprechen“ in Formular, E-Mail und PDF. Leer = Kontaktseite aus den Einstellungen. Das Inhaltselement kann sie mit eigener Weiterleitungsseite überschreiben. Ohne Seite (auch nicht in den Einstellungen) entfällt der Knopf – das Bundle setzt nie selbst einen Link.'];
$l['privacy_page'] = ['Datenschutzseite', 'Link im Kontaktschritt des Formulars, in E-Mail und PDF. Leer = Datenschutzseite aus den Einstellungen. Ohne Seite erscheint kein Link – bitte unbedingt setzen.'];
$l['newsletter_legend'] = 'Newsletter (Brevo) dieser Sprache';
$l['nl_list_id'] = ['Brevo-Verteiler', 'Verteiler für Anmeldungen aus dem Check in dieser Sprache. Leer = Verteiler aus der Konfigurationsdatei (newsletter.brevo_list_id).'];
$l['nl_template_id'] = ['Double-Opt-in-Vorlage', 'Bestätigungsmail in dieser Sprache. Leer = Vorlage aus der Konfigurationsdatei (newsletter.brevo_template_id).'];
$l['nl_redirect_page'] = ['Bestätigungsseite', 'Seite nach dem Klick auf den Bestätigungslink. Leer = newsletter.brevo_redirect_url. Ohne Bestätigungsseite gibt es keine Newsletter-Anmeldung.'];
