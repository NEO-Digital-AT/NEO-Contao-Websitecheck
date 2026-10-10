<?php

declare(strict_types=1);

$l = &$GLOBALS['TL_LANG']['tl_neo_websitecheck_text'];

$l['language_legend'] = 'Language';
$l['placeholders_legend'] = 'Placeholders';
$l['report_legend'] = 'PDF report';
$l['mail_legend'] = 'E-mail to the visitor';
$l['element_legend'] = 'Element on the page';

$l['language'] = ['Language', 'One record per site language. Empty fields keep the bundle\'s default text.'];
$l['placeholders_info'] = ['Placeholders', ''];
$l['placeholdersText'] = 'You can use these placeholders in every field: %s. Example: "Hello {{firstname}}, here is your check for {{domain}}."';

$l['report_cover_eyebrow'] = ['Cover: small line above the title', 'Default: "Free website check"'];
$l['report_cover_title'] = ['Cover: title', 'Default: "Your website check for" (the domain follows)'];
$l['report_next_eyebrow'] = ['Last page: small line', 'Default: "What happens next"'];
$l['report_next_title'] = ['Last page: title', 'Default: "Small steps, no rebuild"'];
$l['report_next_text'] = ['Last page: text', 'Your offer in two or three sentences. Default: "Any web agency … 20 minutes on the phone, free of charge, no sales talk."'];
$l['report_next_promise'] = ['Last page: promise in the contact box', 'Default: "This report is free and does not oblige you to anything."'];
$l['report_a11y_why'] = ['Accessibility page: why it matters', 'Default: "Roughly every twelfth man sees colours differently …"'];

$l['mail_subject'] = ['Subject', 'Default: "Your website check for {{domain}}: grade {{grade}} · {{grade_word}}"'];
$l['mail_greeting'] = ['Greeting', 'Default: "Hello {{firstname}},"'];
$l['mail_intro'] = ['Intro', 'Default: "thank you for your trust. We checked {{pages}} pages of {{domain}}: …"'];
$l['mail_measures_title'] = ['Heading above the measures', 'Default: "What I would change first:"'];
$l['mail_pdf_note'] = ['Note on the PDF attachment', ''];
$l['mail_offer'] = ['Your offer', 'Default: "Any web agency … 20 minutes on the phone, free of charge, no sales talk."'];
$l['mail_cta'] = ['Button label', 'Default: "Talk about it"'];
$l['mail_cta_alt'] = ['Text next to the button', 'Default: "or simply reply to this e-mail"'];
$l['mail_newsletter_note'] = ['Newsletter note', 'Shown only when the visitor ticked the newsletter box.'];
$l['mail_footer'] = ['Footer', 'Default: "You receive this e-mail because … delete them after {{days}} days …"'];
$l['mail_owner_subject'] = ['Subject of your copy', 'Default: "[Website check] {{domain}} · grade {{grade}} · {{firstname}}"'];

$l['fe_url_label'] = ['Label above the address field', 'Default: "Address of your website"'];
$l['fe_submit'] = ['Button "Check now"', ''];
$l['fe_gate_title'] = ['Lead form: title', 'Default: "Your result is ready"'];
$l['fe_gate_text'] = ['Lead form: text', 'Default: "Where may we send it? …"'];
$l['fe_gate_legal'] = ['Lead form: privacy note', 'The privacy policy link is added automatically.'];
$l['fe_newsletter'] = ['Newsletter checkbox text', ''];
$l['fe_gate_submit'] = ['Lead form: button', 'Default: "Show result"'];
$l['fe_disclaimer'] = ['Result: disclaimer', 'Default: "Automated assessment based on …"'];
$l['fe_cta'] = ['Result: button', 'Default: "Talk about the result — free of charge"'];

$l['new'] = ['New language', 'Add texts for another language'];
$l['edit'] = ['Edit', 'Edit texts ID %s'];
$l['copy'] = ['Duplicate', 'Duplicate texts ID %s'];
$l['delete'] = ['Delete', 'Delete texts ID %s'];
$l['show'] = ['Details', 'Show details of ID %s'];
