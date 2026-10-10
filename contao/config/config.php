<?php

declare(strict_types=1);

/*
 * Back-end: "NEO › Website checks" lists every check with its grade and lead
 * data (tl_neo_websitecheck). The "resend" row operation re-sends the report
 * e-mail (key=resend, handled by Backend\ResendOperation).
 */
$GLOBALS['BE_MOD']['neo']['neo_websitecheck'] = [
    'tables' => ['tl_neo_websitecheck'],
    'resend' => [Neo\WebsitecheckBundle\Backend\ResendOperation::class, '__invoke'],
];
