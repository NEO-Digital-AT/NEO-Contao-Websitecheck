<?php

declare(strict_types=1);

use Neo\WebsitecheckBundle\Backend\PreviewOperation;
use Neo\WebsitecheckBundle\Backend\ResendOperation;

/*
 * Back-end modules:
 *  - NEO › Website checks: every check with grade and lead data (tl_neo_websitecheck);
 *    "resend" re-sends the report e-mail (key=resend).
 *  - NEO › Website check settings: brand, design, sender, limits and the
 *    customer-facing texts per language; "preview_pdf" / "preview_mail" render
 *    the templates with real or demo data.
 */
$GLOBALS['BE_MOD']['neo']['neo_websitecheck'] = [
    'tables' => ['tl_neo_websitecheck'],
    'resend' => [ResendOperation::class, '__invoke'],
];

$GLOBALS['BE_MOD']['neo']['neo_websitecheck_settings'] = [
    'tables' => ['tl_neo_websitecheck_settings', 'tl_neo_websitecheck_text'],
    'preview_pdf' => [PreviewOperation::class, 'pdf'],
    'preview_mail' => [PreviewOperation::class, 'mail'],
];
