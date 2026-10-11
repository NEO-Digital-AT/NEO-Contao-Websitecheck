<?php

declare(strict_types=1);

$l = &$GLOBALS['TL_LANG']['tl_neo_websitecheck_config'];

$l['title_legend'] = 'Konfiguracija';
$l['mail_legend'] = 'E-pošta obiskovalcu';
$l['mail_design_legend'] = 'Oblikovanje e-pošte';
$l['pdf_legend'] = 'Poročilo PDF';
$l['pdf_design_legend'] = 'Oblikovanje poročila PDF';
$l['owner_legend'] = 'Vaša kopija';
$l['element_legend'] = 'Element na strani';
$l['fe_result'] = ['Rezultat na strani', 'Kratko: ocena, en kratek stavek in napotilo na PDF. Celotno: dodatno še točke, vsa področja, ukrepi in prednosti. PDF vedno vsebuje vse.'];
$l['fe_result_options'] = ['compact' => 'Kratko (priporočeno)', 'full' => 'Celotno'];
$l['expert_legend'] = 'Interna kopija kot predloga Twig';

$l['title'] = ['Ime', 'Interno ime, npr. „Slovenščina“ ali „Angleščina – hoteli“.'];
$l['language'] = ['Jezik', 'Element brez izrecne dodelitve uporabi konfiguracijo, katere jezik se ujema z njegovo stranjo.'];
$l['mail_subject'] = ['Zadeva', 'Dovoljeni so nadomestni znaki. Privzeto: „Vaše preverjanje spletne strani za {{domain}}: ocena {{grade}} · {{grade_word}}“'];
$l['mail_from_name'] = ['Ime pošiljatelja', 'Prazno = nastavitve / ime podjetja.'];
$l['mail_from_email'] = ['Naslov pošiljatelja', 'Prazno = nastavitve / config.yaml / skrbniški naslov Contao.'];
$l['mail_reply_to'] = ['Naslov za odgovor', 'Prazno = nastavitve.'];
$l['mail_owner_subject'] = ['Zadeva vaše kopije', 'Privzeto: „[Preverjanje spletne strani] {{domain}} · ocena {{grade}} · {{firstname}}“'];
$l['mail_design'] = ['E-pošta', 'Na levi dodajte bloke, jih kliknite v predogledu in uredite na desni. „Naloži predlogo“ nastavi pripravljeno strukturo.'];
$l['pdf_filename'] = ['Ime datoteke PDF', 'Z nadomestnimi znaki, npr. website-check-{{domain}}-{{date}}.pdf (delujejo tudi {{grade}}, {{firstname}}, {{lastname}}, {{company}}).'];
$l['pdf_design'] = ['Poročilo PDF', 'Sestavite stran za stranjo: naslovnica, podatkovni bloki, lastna besedila in slike, prelomi strani. Predogled uporablja zadnje dokončano preverjanje ali predstavitvene podatke.'];
$l['mail_body'] = ['E-pošta kot koda Twig (napredni način)', 'Samo za skrbnike. Ureja se v oblikovalniku prek „Napredno (Twig)“. Če je izpolnjeno, se namesto vizualnega oblikovanja uporabi ta koda.'];
$l['owner_body'] = ['Vaša kopija kot predloga Twig', 'Prazno = priložena predloga.'];
$l['pdf_body'] = ['Poročilo PDF kot koda Twig (napredni način)', 'Samo za skrbnike. Ureja se v oblikovalniku prek „Napredno (Twig)“. Če je izpolnjeno, se namesto vizualnega oblikovanja uporabi ta koda.'];

$l['fe_url_label'] = ['Oznaka nad poljem za naslov', 'Privzeto: „Naslov vaše spletne strani“'];
$l['fe_submit'] = ['Gumb „Preveri zdaj“', ''];
$l['fe_gate_title'] = ['Kontaktni obrazec: naslov', 'Privzeto: „Vaš rezultat je pripravljen“'];
$l['fe_gate_text'] = ['Kontaktni obrazec: besedilo', 'Privzeto: „Kam vam ga lahko pošljemo? …“'];
$l['fe_gate_legal'] = ['Kontaktni obrazec: obvestilo o zasebnosti', 'Povezava na stran o zasebnosti se doda samodejno.'];
$l['fe_newsletter'] = ['Besedilo potrditvenega polja za e-novice', ''];
$l['fe_gate_submit'] = ['Kontaktni obrazec: gumb', 'Privzeto: „Prikaži rezultat“'];
$l['fe_disclaimer'] = ['Rezultat: opomba', 'Privzeto: „Samodejna ocena na podlagi …“'];
$l['fe_cta'] = ['Rezultat: gumb', 'Privzeto: „Brezplačno se pogovorimo o rezultatu“'];

$l['new'] = ['Nova konfiguracija', 'Dodaj konfiguracijo za drug jezik ali namen uporabe'];
$l['edit'] = ['Uredi', 'Uredi konfiguracijo ID %s'];
$l['copy'] = ['Podvoji', 'Podvoji konfiguracijo ID %s'];
$l['delete'] = ['Izbriši', 'Izbriši konfiguracijo ID %s'];
$l['show'] = ['Podrobnosti', 'Prikaži podrobnosti ID %s'];
$l['preview_pdf'] = ['Predogled PDF', 'Odpri oblikovano poročilo kot PDF'];
$l['preview_mail'] = ['Predogled e-pošte', 'Odpri oblikovano e-pošto v brskalniku'];
$l['automatic'] = 'Samodejno (glede na jezik strani)';
$l['element_config'] = ['Konfiguracija', 'Katero konfiguracijo preverjanja spletne strani (besedila, e-pošta, PDF) uporablja ta element. Samodejno = tista, ki ustreza jeziku te strani.'];

// Multilingual sites: links and newsletter per configuration (= per language); empty = global value.
$l['links_legend'] = 'Strani tega jezika';
$l['contact_page'] = ['Kontaktna stran', 'Cilj gumba „Pogovorimo se o rezultatu“ v obrazcu, e-pošti in PDF. Prazno = kontaktna stran iz nastavitev. Vsebinski element jo lahko preglasi z lastno stranjo za preusmeritev. Brez strani (tukaj ali v nastavitvah) se gumb izpusti – modul nikoli sam ne nastavi povezave.'];
$l['privacy_page'] = ['Stran o zasebnosti', 'Povezava v kontaktnem koraku obrazca, v e-pošti in PDF. Prazno = stran o zasebnosti iz nastavitev. Brez strani povezava ni prikazana – prosimo, nastavite jo.'];
$l['newsletter_legend'] = 'E-novice tega jezika';
$l['nl_list_id'] = ['Seznam Brevo', 'Seznam za prijave iz preverjanja v tem jeziku. Prazno = seznam iz konfiguracijske datoteke (newsletter.brevo_list_id).'];
$l['nl_template_id'] = ['Predloga za dvojno potrditev', 'Potrditvena e-pošta v tem jeziku. Prazno = predloga iz konfiguracijske datoteke (newsletter.brevo_template_id).'];
$l['nl_redirect_page'] = ['Potrditvena stran', 'Stran po kliku na potrditveno povezavo. Prazno = newsletter.brevo_redirect_url. Brez potrditvene strani prijave na e-novice ni.'];
$l['nl_list_id_contao'] = ['Kanal e-novic (Contao)', 'Kanal e-novic Contao za prijave iz preverjanja v tem jeziku.'];
$l['nl_redirect_page_contao'] = ['Potrditvena stran', 'Stran z modulom Contao »Naročnina«: potrditvena povezava v e-sporočilu vodi tja in aktivira prijavo. Besedilo e-sporočila je iz tega modula. Brez potrditvene strani prijave na e-novice ni.'];
$l['ask_contact'] = ['Vprašaj za dovoljenje za stik', 'V obrazcu prikaže prostovoljno potrditveno polje »Glede tega rezultata me lahko enkrat kontaktirate po e-pošti«. Besedilo in čas se shranita ob preverjanju.'];
$l['fe_contact'] = ['Besedilo polja »enkratni stik«', ''];
