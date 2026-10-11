<?php

declare(strict_types=1);

$l = &$GLOBALS['TL_LANG']['tl_neo_websitecheck_config'];

$l['title_legend'] = 'Konfiguracija';
$l['mail_legend'] = 'E-mail posjetitelju';
$l['mail_design_legend'] = 'Oblikovanje e-maila';
$l['pdf_legend'] = 'PDF izvještaj';
$l['pdf_design_legend'] = 'Oblikovanje PDF izvještaja';
$l['owner_legend'] = 'Vaša kopija';
$l['element_legend'] = 'Element na stranici';
$l['fe_result'] = ['Rezultat na stranici', 'Kratko: ocjena, jedna kratka rečenica i napomena o PDF-u. Potpuno: dodatno bodovi, sva područja, mjere i prednosti. PDF uvijek sadrži sve.'];
$l['fe_result_options'] = ['compact' => 'Kratko (preporučeno)', 'full' => 'Potpuno'];
$l['expert_legend'] = 'Interna kopija kao Twig predložak';

$l['title'] = ['Naziv', 'Interni naziv, npr. „Hrvatski” ili „Engleski – hoteli”.'];
$l['language'] = ['Jezik', 'Element bez izričite dodjele koristi konfiguraciju čiji jezik odgovara njegovoj stranici.'];
$l['mail_subject'] = ['Predmet', 'Dopušteni su zamjenski izrazi. Zadano: „Vaša provjera web-stranice za {{domain}}: ocjena {{grade}} · {{grade_word}}”'];
$l['mail_from_name'] = ['Ime pošiljatelja', 'Prazno = postavke / naziv tvrtke.'];
$l['mail_from_email'] = ['Adresa pošiljatelja', 'Prazno = postavke / config.yaml / adresa Contao administratora.'];
$l['mail_reply_to'] = ['Adresa za odgovor', 'Prazno = postavke.'];
$l['mail_owner_subject'] = ['Predmet vaše kopije', 'Zadano: „[Provjera web-stranice] {{domain}} · ocjena {{grade}} · {{firstname}}”'];
$l['mail_design'] = ['E-mail', 'Dodajte blokove slijeva, kliknite ih u pregledu i uredite desno. „Učitaj predložak” postavlja gotovu strukturu.'];
$l['pdf_filename'] = ['Naziv PDF datoteke', 'Sa zamjenskim izrazima, npr. website-check-{{domain}}-{{date}}.pdf (rade i {{grade}}, {{firstname}}, {{lastname}}, {{company}}).'];
$l['pdf_design'] = ['PDF izvještaj', 'Složite stranicu po stranicu: naslovnica, podatkovni blokovi, vlastiti tekstovi i slike, prijelomi stranica. Pregled koristi posljednju dovršenu provjeru ili demo podatke.'];
$l['mail_body'] = ['E-mail kao Twig kod (napredni način)', 'Samo za administratore. Uređuje se u dizajneru putem „Napredno (Twig)”. Ako je popunjeno, umjesto vizualnog dizajna koristi se ovaj kod.'];
$l['owner_body'] = ['Vaša kopija kao Twig predložak', 'Prazno = ugrađeni predložak.'];
$l['pdf_body'] = ['PDF izvještaj kao Twig kod (napredni način)', 'Samo za administratore. Uređuje se u dizajneru putem „Napredno (Twig)”. Ako je popunjeno, umjesto vizualnog dizajna koristi se ovaj kod.'];

$l['fe_url_label'] = ['Oznaka iznad polja za adresu', 'Zadano: „Adresa vaše web-stranice”'];
$l['fe_submit'] = ['Gumb „Provjeri sada”', ''];
$l['fe_gate_title'] = ['Kontaktni obrazac: naslov', 'Zadano: „Vaš je rezultat spreman”'];
$l['fe_gate_text'] = ['Kontaktni obrazac: tekst', 'Zadano: „Kamo da ga pošaljemo? …”'];
$l['fe_gate_legal'] = ['Kontaktni obrazac: napomena o privatnosti', 'Poveznica na stranicu o privatnosti dodaje se automatski.'];
$l['fe_newsletter'] = ['Tekst kućice za newsletter', ''];
$l['fe_gate_submit'] = ['Kontaktni obrazac: gumb', 'Zadano: „Prikaži rezultat”'];
$l['fe_disclaimer'] = ['Rezultat: napomena', 'Zadano: „Automatska procjena na temelju …”'];
$l['fe_cta'] = ['Rezultat: gumb', 'Zadano: „Besplatno razgovarajmo o rezultatu”'];

$l['new'] = ['Nova konfiguracija', 'Dodaj konfiguraciju za drugi jezik ili namjenu'];
$l['edit'] = ['Uredi', 'Uredi konfiguraciju ID %s'];
$l['copy'] = ['Dupliciraj', 'Dupliciraj konfiguraciju ID %s'];
$l['delete'] = ['Izbriši', 'Izbriši konfiguraciju ID %s'];
$l['show'] = ['Pojedinosti', 'Prikaži pojedinosti za ID %s'];
$l['preview_pdf'] = ['Pregled PDF-a', 'Otvori oblikovani izvještaj kao PDF'];
$l['preview_mail'] = ['Pregled e-maila', 'Otvori oblikovani e-mail u pregledniku'];
$l['automatic'] = 'Automatski (prema jeziku stranice)';
$l['element_config'] = ['Konfiguracija', 'Koju konfiguraciju provjere web-stranice (tekstovi, e-mail, PDF) ovaj element koristi. Automatski = ona koja odgovara jeziku ove stranice.'];

// Višejezična web-mjesta: poveznice i newsletter po konfiguraciji (= po jeziku); prazno = globalna vrijednost.
$l['links_legend'] = 'Stranice ovog jezika';
$l['contact_page'] = ['Kontaktna stranica', 'Odredište gumba „Razgovarajmo o rezultatu” u obrascu, e-mailu i PDF-u. Prazno = kontaktna stranica iz postavki. Element sadržaja to može nadjačati vlastitom stranicom za preusmjeravanje. Bez stranice (ovdje ili u postavkama) gumb se izostavlja – modul nikada sam ne postavlja poveznicu.'];
$l['privacy_page'] = ['Stranica o privatnosti', 'Poveznica u kontaktnom koraku obrasca, u e-mailu i PDF-u. Prazno = stranica o privatnosti iz postavki. Bez stranice poveznica se ne prikazuje – molimo postavite je.'];
$l['newsletter_legend'] = 'Newsletter (Brevo) ovog jezika';
$l['nl_list_id'] = ['Brevo popis', 'Popis za prijave iz provjere na ovom jeziku. Prazno = popis iz konfiguracijske datoteke (newsletter.brevo_list_id).'];
$l['nl_template_id'] = ['Predložak dvostruke potvrde', 'E-mail za potvrdu na ovom jeziku. Prazno = predložak iz konfiguracijske datoteke (newsletter.brevo_template_id).'];
$l['nl_redirect_page'] = ['Stranica potvrde', 'Stranica nakon klika na poveznicu za potvrdu. Prazno = newsletter.brevo_redirect_url. Bez stranice potvrde nema prijave na newsletter.'];
