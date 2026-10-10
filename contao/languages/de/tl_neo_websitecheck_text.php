<?php

declare(strict_types=1);

$l = &$GLOBALS['TL_LANG']['tl_neo_websitecheck_text'];

$l['language_legend'] = 'Sprache';
$l['placeholders_legend'] = 'Platzhalter';
$l['mail_legend'] = 'E-Mail an den Besucher';
$l['mail_body_legend'] = 'Komplette E-Mail-Vorlage (fortgeschritten)';
$l['owner_legend'] = 'Ihre Kopie';
$l['pdf_legend'] = 'PDF-Bericht';
$l['pdf_body_legend'] = 'Komplette PDF-Vorlage (fortgeschritten)';

$l['mail_from_name'] = ['Absendername für diese Sprache', 'Leer = Einstellungen / Firmenname.'];
$l['mail_from_email'] = ['Absenderadresse für diese Sprache', 'Leer = Einstellungen / config.yaml.'];
$l['mail_reply_to'] = ['Antwortadresse für diese Sprache', 'Leer = Einstellungen.'];
$l['mail_body'] = ['E-Mail (komplette HTML/Twig-Vorlage)', 'Die ganze E-Mail. Vorbefüllt mit der aktuellen Vorlage; Platzhalter wie {{firstname}} und alle Vorlagen-Variablen (result, brand, vars …) stehen zur Verfügung. Leer = mitgelieferte Vorlage, die dann die Textbausteine oben verwendet.'];
$l['owner_body'] = ['Ihre Kopie (komplette Vorlage)', 'Die E-Mail, die Sie mit den Kontaktdaten bekommen. Gleiche Regeln wie bei der Besucher-E-Mail.'];
$l['pdf_filename'] = ['PDF-Dateiname', 'Mit Platzhaltern, z. B. website-check-{{domain}}-{{date}}.pdf ({{grade}}, {{firstname}}, {{lastname}}, {{company}} gehen auch).'];
$l['pdf_body'] = ['PDF-Bericht (komplette HTML/Twig-Vorlage)', 'Der ganze sechsseitige Bericht als HTML/Twig. Vorbefüllt mit der aktuellen Vorlage, damit Layout, Farben und Texte frei änderbar sind. Leer = mitgelieferte Vorlage mit den Bausteinen oben.'];
$l['element_legend'] = 'Element auf der Seite';

$l['language'] = ['Sprache', 'Ein Datensatz je Seitensprache. Leere Felder behalten den mitgelieferten Text.'];
$l['placeholders_info'] = ['Platzhalter', ''];
$l['placeholdersText'] = 'In jedem Feld können Sie diese Platzhalter verwenden: %s. Beispiel: „Hallo {{firstname}}, hier ist Ihr Check für {{domain}}.“';

$l['report_cover_eyebrow'] = ['Deckblatt: kleine Zeile über dem Titel', 'Standard: „Kostenloser Website-Check“'];
$l['report_cover_title'] = ['Deckblatt: Titel', 'Standard: „Ihr Website-Check für“ (die Domain folgt)'];
$l['report_next_eyebrow'] = ['Letzte Seite: kleine Zeile', 'Standard: „So geht es weiter“'];
$l['report_next_title'] = ['Letzte Seite: Titel', 'Standard: „Kleine Schritte, kein Umbau“'];
$l['report_next_text'] = ['Letzte Seite: Text', 'Ihr Angebot in zwei, drei Sätzen. Standard: „Die Maßnahmen von Seite 2 kann jede Webagentur … 20 Minuten am Telefon, kostenlos, kein Verkaufsgespräch.“'];
$l['report_next_promise'] = ['Letzte Seite: Zusage im Kontaktkasten', 'Standard: „Dieser Bericht ist kostenlos und verpflichtet Sie zu nichts.“'];
$l['report_a11y_why'] = ['Seite Barrierefreiheit: Warum das zählt', 'Standard: „Rund jeder zwölfte Mann sieht Farben anders …“'];

$l['mail_subject'] = ['Betreff', 'Standard: „Ihr Website-Check für {{domain}}: Note {{grade}} · {{grade_word}}“'];
$l['mail_greeting'] = ['Anrede', 'Standard: „Hallo {{firstname}},“'];
$l['mail_intro'] = ['Einleitung', 'Standard: „danke für Ihr Vertrauen. Wir haben {{pages}} Seiten von {{domain}} geprüft: …“'];
$l['mail_measures_title'] = ['Überschrift über den Maßnahmen', 'Standard: „Was ich zuerst ändern würde:“'];
$l['mail_pdf_note'] = ['Hinweis auf den PDF-Anhang', ''];
$l['mail_offer'] = ['Ihr Angebot', 'Standard: „Diese Punkte kann jede Webagentur … 20 Minuten am Telefon, kostenlos, kein Verkaufsgespräch.“'];
$l['mail_cta'] = ['Beschriftung des Knopfs', 'Standard: „Termin für 20 Minuten“'];
$l['mail_cta_alt'] = ['Text neben dem Knopf', 'Standard: „oder einfach auf diese E-Mail antworten“'];
$l['mail_newsletter_note'] = ['Newsletter-Hinweis', 'Nur sichtbar, wenn der Besucher das Newsletter-Häkchen gesetzt hat.'];
$l['mail_footer'] = ['Fußzeile', 'Standard: „Sie erhalten diese E-Mail, weil … löschen sie nach {{days}} Tagen …“'];
$l['mail_owner_subject'] = ['Betreff Ihrer Kopie', 'Standard: „[Website-Check] {{domain}} · Note {{grade}} · {{firstname}}“'];

$l['fe_url_label'] = ['Beschriftung über dem Adressfeld', 'Standard: „Adresse Ihrer Website“'];
$l['fe_submit'] = ['Knopf „Jetzt prüfen“', ''];
$l['fe_gate_title'] = ['Kontaktformular: Titel', 'Standard: „Ihr Ergebnis ist fertig“'];
$l['fe_gate_text'] = ['Kontaktformular: Text', 'Standard: „Wohin dürfen wir es schicken? …“'];
$l['fe_gate_legal'] = ['Kontaktformular: Datenschutzhinweis', 'Der Link zur Datenschutzseite wird automatisch angehängt.'];
$l['fe_newsletter'] = ['Text des Newsletter-Häkchens', ''];
$l['fe_gate_submit'] = ['Kontaktformular: Knopf', 'Standard: „Ergebnis ansehen“'];
$l['fe_disclaimer'] = ['Ergebnis: Hinweis', 'Standard: „Automatische Einschätzung auf Basis von …“'];
$l['fe_cta'] = ['Ergebnis: Knopf', 'Standard: „Ergebnis besprechen, kostenlos“'];

$l['new'] = ['Neue Sprache', 'Texte für eine weitere Sprache anlegen'];
$l['edit'] = ['Bearbeiten', 'Texte ID %s bearbeiten'];
$l['recordLabel'] = 'Sprache %s';
$l['copy'] = ['Duplizieren', 'Texte ID %s duplizieren'];
$l['delete'] = ['Löschen', 'Texte ID %s löschen'];
$l['show'] = ['Details', 'Details von ID %s anzeigen'];
