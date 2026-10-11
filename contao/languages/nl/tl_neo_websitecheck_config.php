<?php

declare(strict_types=1);

$l = &$GLOBALS['TL_LANG']['tl_neo_websitecheck_config'];

$l['title_legend'] = 'Configuratie';
$l['mail_legend'] = 'E-mail aan de bezoeker';
$l['mail_design_legend'] = 'E-mail vormgeven';
$l['pdf_legend'] = 'Pdf-rapport';
$l['pdf_design_legend'] = 'Pdf-rapport vormgeven';
$l['owner_legend'] = 'Uw kopie';
$l['element_legend'] = 'Het element op de pagina';
$l['fe_result'] = ['Resultaat op de pagina', 'Kort: cijfer, één korte zin en de verwijzing naar de pdf. Volledig: daarnaast scores, alle onderdelen, maatregelen en sterke punten. De pdf bevat altijd alles.'];
$l['fe_result_options'] = ['compact' => 'Kort (aanbevolen)', 'full' => 'Volledig'];
$l['expert_legend'] = 'Interne kopie als Twig-template';

$l['title'] = ['Naam', 'Interne naam, bijv. “Nederlands” of “Engels – hotels”.'];
$l['language'] = ['Taal', 'Een element zonder vaste toewijzing gebruikt de configuratie waarvan de taal overeenkomt met de taal van de pagina.'];
$l['mail_subject'] = ['Onderwerp', 'Placeholders toegestaan. Standaard: “Uw websitecheck voor {{domain}}: cijfer {{grade}} · {{grade_word}}”'];
$l['mail_from_name'] = ['Naam afzender', 'Leeg = instellingen / bedrijfsnaam.'];
$l['mail_from_email'] = ['Adres afzender', 'Leeg = instellingen / config.yaml / beheerdersadres van Contao.'];
$l['mail_reply_to'] = ['Antwoordadres', 'Leeg = instellingen.'];
$l['mail_owner_subject'] = ['Onderwerp van uw kopie', 'Standaard: “[Websitecheck] {{domain}} · cijfer {{grade}} · {{firstname}}”'];
$l['mail_design'] = ['E-mail', 'Voeg links blokken toe, klik ze aan in de voorbeeldweergave en bewerk ze rechts. “Sjabloon laden” zet een kant-en-klare opbouw klaar.'];
$l['pdf_filename'] = ['Bestandsnaam pdf', 'Met placeholders, bijv. website-check-{{domain}}-{{date}}.pdf ({{grade}}, {{firstname}}, {{lastname}}, {{company}} werken ook).'];
$l['pdf_design'] = ['Pdf-rapport', 'Stel het pagina voor pagina samen: voorblad, gegevensblokken, eigen teksten en afbeeldingen, pagina-einden. De voorbeeldweergave gebruikt de laatste afgeronde check of voorbeeldgegevens.'];
$l['mail_body'] = ['E-mail als Twig-code (geavanceerde modus)', 'Alleen voor beheerders. Wordt in de ontwerper bewerkt via “Geavanceerd (Twig)”. Zodra hier code staat, wordt die gebruikt in plaats van het visuele ontwerp.'];
$l['owner_body'] = ['Uw kopie als Twig-template', 'Leeg = meegeleverde template.'];
$l['pdf_body'] = ['Pdf-rapport als Twig-code (geavanceerde modus)', 'Alleen voor beheerders. Wordt in de ontwerper bewerkt via “Geavanceerd (Twig)”. Zodra hier code staat, wordt die gebruikt in plaats van het visuele ontwerp.'];

$l['fe_url_label'] = ['Label boven het adresveld', 'Standaard: “Adres van uw website”'];
$l['fe_submit'] = ['Knop “Nu controleren”', ''];
$l['fe_gate_title'] = ['Contactformulier: titel', 'Standaard: “Uw resultaat staat klaar”'];
$l['fe_gate_text'] = ['Contactformulier: tekst', 'Standaard: “Waar mogen we het naartoe sturen? …”'];
$l['fe_gate_legal'] = ['Contactformulier: privacyvermelding', 'De link naar de privacypagina wordt automatisch toegevoegd.'];
$l['fe_newsletter'] = ['Tekst bij het vakje voor de nieuwsbrief', ''];
$l['fe_gate_submit'] = ['Contactformulier: knop', 'Standaard: “Resultaat tonen”'];
$l['fe_disclaimer'] = ['Resultaat: opmerking', 'Standaard: “Automatische beoordeling op basis van …”'];
$l['fe_cta'] = ['Resultaat: knop', 'Standaard: “Het resultaat gratis bespreken”'];

$l['new'] = ['Nieuwe configuratie', 'Een configuratie toevoegen voor een andere taal of toepassing'];
$l['edit'] = ['Bewerken', 'Configuratie ID %s bewerken'];
$l['copy'] = ['Dupliceren', 'Configuratie ID %s dupliceren'];
$l['delete'] = ['Verwijderen', 'Configuratie ID %s verwijderen'];
$l['show'] = ['Details', 'Details van ID %s tonen'];
$l['preview_pdf'] = ['Pdf-voorbeeld', 'Het vormgegeven rapport als pdf openen'];
$l['preview_mail'] = ['E-mailvoorbeeld', 'De vormgegeven e-mail in de browser openen'];
$l['automatic'] = 'Automatisch (op basis van de paginataal)';
$l['element_config'] = ['Configuratie', 'Welke configuratie van de websitecheck (teksten, e-mail, pdf) dit element gebruikt. Automatisch = de configuratie die past bij de taal van deze pagina.'];

// Multilingual sites: links and newsletter per configuration (= per language); empty = global value.
$l['links_legend'] = 'Pagina’s in deze taal';
$l['contact_page'] = ['Contactpagina', 'Doel van “Resultaat bespreken” in het formulier, de e-mail en de pdf. Leeg = contactpagina uit de instellingen. Het inhoudselement kan dit met een eigen doorverwijspagina overschrijven. Zonder pagina (hier of in de instellingen) vervalt de knop – de bundle zet nooit zelf een link.'];
$l['privacy_page'] = ['Privacypagina', 'Link in de contactstap van het formulier, in de e-mail en in de pdf. Leeg = privacypagina uit de instellingen. Zonder pagina wordt er geen link getoond – stel deze dus in.'];
$l['newsletter_legend'] = 'Nieuwsbrief in deze taal';
$l['nl_list_id'] = ['Brevo-lijst', 'Lijst voor aanmeldingen via de check in deze taal. Leeg = lijst uit het configuratiebestand (newsletter.brevo_list_id).'];
$l['nl_template_id'] = ['Template voor dubbele opt-in', 'Bevestigingsmail in deze taal. Leeg = template uit het configuratiebestand (newsletter.brevo_template_id).'];
$l['nl_redirect_page'] = ['Bevestigingspagina', 'Pagina na het klikken op de bevestigingslink. Leeg = newsletter.brevo_redirect_url. Zonder bevestigingspagina is er geen aanmelding voor de nieuwsbrief.'];
$l['nl_list_id_contao'] = ['Nieuwsbriefkanaal (Contao)', 'Contao-nieuwsbriefkanaal voor aanmeldingen via de check in deze taal.'];
$l['nl_redirect_page_contao'] = ['Bevestigingspagina', 'Pagina met de Contao-module „Abonneren”: de bevestigingslink in de e-mail leidt daarheen en activeert de aanmelding. De e-mailtekst komt uit die module. Zonder bevestigingspagina is er geen aanmelding voor de nieuwsbrief.'];
