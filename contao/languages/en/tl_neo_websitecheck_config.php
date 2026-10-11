<?php

declare(strict_types=1);

$l = &$GLOBALS['TL_LANG']['tl_neo_websitecheck_config'];

$l['title_legend'] = 'Configuration';
$l['mail_legend'] = 'E-mail to the visitor';
$l['mail_design_legend'] = 'Design the e-mail';
$l['pdf_legend'] = 'PDF report';
$l['pdf_design_legend'] = 'Design the PDF report';
$l['owner_legend'] = 'Your copy';
$l['element_legend'] = 'The element on the page';
$l['fe_result'] = ['Result on the page', 'Short: grade, one short sentence and the hint to the PDF. Full: additionally scores, all areas, measures and strengths. The PDF always has everything.'];
$l['fe_result_options'] = ['compact' => 'Short (recommended)', 'full' => 'Full'];
$l['expert_legend'] = 'Internal copy as Twig template';

$l['title'] = ['Name', 'Internal name, e.g. “German” or “English – hotels”.'];
$l['language'] = ['Language', 'An element without an explicit assignment uses the configuration whose language matches its page.'];
$l['mail_subject'] = ['Subject', 'Placeholders allowed. Default: “Your website check for {{domain}}: grade {{grade}} · {{grade_word}}”'];
$l['mail_from_name'] = ['Sender name', 'Empty = settings / company name.'];
$l['mail_from_email'] = ['Sender address', 'Empty = settings / config.yaml / Contao admin address.'];
$l['mail_reply_to'] = ['Reply-to address', 'Empty = settings.'];
$l['mail_owner_subject'] = ['Subject of your copy', 'Default: “[Website check] {{domain}} · grade {{grade}} · {{firstname}}”'];
$l['mail_design'] = ['E-mail', 'Add blocks on the left, click them in the preview and edit on the right. “Load template” sets a ready-made structure.'];
$l['pdf_filename'] = ['PDF file name', 'With placeholders, e.g. website-check-{{domain}}-{{date}}.pdf ({{grade}}, {{firstname}}, {{lastname}}, {{company}} work too).'];
$l['pdf_design'] = ['PDF report', 'Compose page by page: cover, data blocks, your own texts and images, page breaks. The preview uses the latest finished check or demo data.'];
$l['mail_body'] = ['E-mail as Twig code (advanced mode)', 'Administrators only. Edited in the designer via “Advanced (Twig)”. When filled, this code is used instead of the visual design.'];
$l['owner_body'] = ['Your copy as Twig template', 'Empty = bundled template.'];
$l['pdf_body'] = ['PDF report as Twig code (advanced mode)', 'Administrators only. Edited in the designer via “Advanced (Twig)”. When filled, this code is used instead of the visual design.'];

$l['fe_url_label'] = ['Label above the address field', 'Default: “Address of your website”'];
$l['fe_submit'] = ['“Check now” button', ''];
$l['fe_gate_title'] = ['Contact form: title', 'Default: “Your result is ready”'];
$l['fe_gate_text'] = ['Contact form: text', 'Default: “Where may we send it? …”'];
$l['fe_gate_legal'] = ['Contact form: privacy note', 'The link to the privacy page is appended automatically.'];
$l['fe_newsletter'] = ['Newsletter checkbox text', ''];
$l['fe_gate_submit'] = ['Contact form: button', 'Default: “Show result”'];
$l['fe_disclaimer'] = ['Result: note', 'Default: “Automatic assessment based on …”'];
$l['fe_cta'] = ['Result: button', 'Default: “Discuss the result, free of charge”'];

$l['new'] = ['New configuration', 'Add a configuration for another language or use case'];
$l['edit'] = ['Edit', 'Edit configuration ID %s'];
$l['copy'] = ['Duplicate', 'Duplicate configuration ID %s'];
$l['delete'] = ['Delete', 'Delete configuration ID %s'];
$l['show'] = ['Details', 'Show details of ID %s'];
$l['preview_pdf'] = ['PDF preview', 'Open the designed report as PDF'];
$l['preview_mail'] = ['E-mail preview', 'Open the designed e-mail in the browser'];
$l['automatic'] = 'Automatic (by page language)';
$l['element_config'] = ['Configuration', 'Which website check configuration (texts, e-mail, PDF) this element uses. Automatic = the one matching the language of this page.'];
