<?php

declare(strict_types=1);

$l = &$GLOBALS['TL_LANG']['tl_neo_websitecheck_settings'];

$l['brand_legend'] = 'Chi invia il report';
$l['design_legend'] = 'Design';
$l['texts_legend'] = 'Configurazioni (testi, e-mail, PDF per lingua)';
$l['mail_legend'] = 'E-mail';
$l['limits_legend'] = 'Limiti';

$l['brand_name'] = ['Azienda / agenzia', 'Mostrato nell’intestazione del report, nell’e-mail e nella firma. Vuoto = titolo del sito.'];
$l['owner_name'] = ['Referente', 'La persona che firma il report, ad es. "Erich Nigg".'];
$l['phone'] = ['Telefono', 'Mostrato nel report e nella firma dell’e-mail.'];
$l['website_url'] = ['Sito web', 'Il Suo sito web, ad es. https://example.com'];
$l['contact_page'] = ['Pagina di contatto', 'Destinazione dei pulsanti "parliamo del risultato" nel report, nell’e-mail e nell’elemento (l’elemento può sostituirla).'];
$l['privacy_page'] = ['Pagina dell’informativa sulla privacy', 'Collegata sotto il modulo di contatto.'];
$l['logo'] = ['Logo', 'PNG, JPG o SVG per l’intestazione del report e l’e-mail. Scuro su sfondo chiaro.'];
$l['logo_dark'] = ['Logo per sfondo scuro', 'Variante facoltativa per l’intestazione colorata dell’e-mail; vuoto = il logo qui sopra.'];
$l['portrait'] = ['Foto ritratto', 'Facoltativa, mostrata nel riquadro "come procedere" e nella firma dell’e-mail.'];
$l['color_primary'] = ['Colore primario', 'PDF ed e-mail: titoli, riquadri, intestazione dell’e-mail (esadecimale, ad es. 1f2328). L’elemento sul sito riceve lo stile dal CSS del tema (proprietà --nwc-*).'];
$l['color_accent'] = ['Colore di accento', 'PDF ed e-mail: pulsanti, evidenziazioni, linee (esadecimale, ad es. 0969da).'];
$l['texts_info'] = ['Configurazioni', ''];
$l['from_name'] = ['Nome del mittente', 'Vuoto = nome dell’azienda.'];
$l['from_email'] = ['Indirizzo del mittente', 'Vuoto = config.yaml o l’e-mail dell’amministratore Contao.'];
$l['reply_to'] = ['Indirizzo per le risposte', 'Dove arrivano le risposte all’e-mail del report.'];
$l['notify_email'] = ['Copia a', 'Riceve una copia di ogni report con nome, e-mail e scelta sulla newsletter del visitatore. Vuoto = nessuna copia.'];
$l['checks_per_ip_hour'] = ['Check per visitatore all’ora', 'Protegge da abusi e costi. 0 = valore da config.yaml (predefinito 2).'];
$l['checks_per_ip'] = ['Check per visitatore al giorno', '0 = valore da config.yaml (predefinito 10).'];
$l['cache_hours'] = ['Cache in ore', 'Stesso dominio di nuovo entro questo tempo: risultato salvato. 0 = config.yaml (24).'];
$l['retention_days'] = ['Conservazione in giorni', 'Dopo questo periodo i check, compresi i dati dei contatti, vengono cancellati. 0 = config.yaml (90).'];
$l['max_pages'] = ['Pagine per check', 'Pagina iniziale più le sottopagine principali, al massimo 8. 0 = config.yaml (5).'];

$l['edit'] = ['Modifica', 'Modifica le impostazioni'];
$l['configs'] = ['Configurazioni', 'Design di e-mail, PDF e testi per lingua'];
$l['preview_pdf'] = ['Anteprima PDF', 'Genera il report PDF con l’ultimo check completato (o con dati dimostrativi)'];
$l['preview_mail'] = ['Anteprima e-mail', 'Genera l’e-mail del report con l’ultimo check completato (o con dati dimostrativi)'];
$l['textsButton'] = 'Modifica configurazioni (%d)';
$l['textsNone'] = 'Ancora nessuna configurazione. Ne crei una per lingua: lì si progettano l’e-mail e il report PDF, l’oggetto, il mittente e i testi dell’elemento. Fino ad allora valgono i template inclusi.';
$l['textsList'] = 'Esistenti: %s';

$l['api_legend'] = 'Chiavi e IA';
$l['api_info'] = 'Da dove arrivano le chiavi';
$l['psi_api_key'] = ['Chiave PageSpeed', 'Chiave API Google per PageSpeed Insights (misura dei tempi di caricamento). Salvata cifrata e mai più mostrata; lasciare vuoto per mantenere la chiave salvata.'];
$l['ai_api_key'] = ['Chiave IA', 'Chiave del fornitore IA (ad es. Requesty) per la valutazione «con gli occhi di un visitatore». Salvata cifrata; lasciare vuoto per mantenerla.'];
$l['ai_model'] = ['Modello IA', 'Appena è nota una chiave IA, scelta tra i modelli del fornitore. Vuoto = valore della configurazione del server.'];
$l['ai_base_url'] = ['Indirizzo del fornitore IA', 'Indirizzo compatibile OpenAI, ad es. https://router.eu.requesty.ai/v1. Vuoto = configurazione del server.'];
$l['apiIntro'] = 'I valori inseriti qui prevalgono sulla configurazione del server (config.yaml / ambiente). Ciò che qui è vuoto viene da lì.';
$l['apiPsi'] = 'PageSpeed';
$l['apiAi'] = 'IA';
$l['apiModel'] = 'Modello in uso';
$l['apiFromDb'] = 'salvata qui nel back end';
$l['apiFromConfig'] = 'dalla configurazione del server';
$l['apiMissing'] = 'mancante — questa parte del check viene saltata';
$l['modelFromConfig'] = '– configurazione del server (%s) –';
$l['test_api'] = ['Verifica connessioni', ''];
