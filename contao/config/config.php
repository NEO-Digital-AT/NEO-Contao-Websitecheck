<?php

declare(strict_types=1);

use Neo\WebsitecheckBundle\Backend\DesignerOperation;
use Neo\WebsitecheckBundle\Backend\ApiTestOperation;
use Neo\WebsitecheckBundle\Backend\PreviewOperation;
use Neo\WebsitecheckBundle\Backend\ResendOperation;
use Neo\WebsitecheckBundle\Widget\DesignerWidget;

$GLOBALS['BE_MOD']['neo']['neo_websitecheck'] = [
    'tables' => ['tl_neo_websitecheck'],
    'resend' => [ResendOperation::class, '__invoke'],
];

/*
 * Settings module: the single settings record plus the configurations per
 * language (child table). "key=" operations: previews with the latest check
 * or demo data, and the endpoints of the visual designer (live preview, PDF
 * of a draft, presets, image upload, Twig export).
 */
$GLOBALS['BE_MOD']['neo']['neo_websitecheck_settings'] = [
    'tables' => ['tl_neo_websitecheck_settings', 'tl_neo_websitecheck_config'],
    'preview_pdf' => [PreviewOperation::class, 'pdf'],
    'preview_mail' => [PreviewOperation::class, 'mail'],
    'test_api' => [ApiTestOperation::class, '__invoke'],
    'designer_preview' => [DesignerOperation::class, 'preview'],
    'designer_pdf' => [DesignerOperation::class, 'pdf'],
    'designer_preset' => [DesignerOperation::class, 'preset'],
    'designer_upload' => [DesignerOperation::class, 'upload'],
    'designer_twig' => [DesignerOperation::class, 'twig'],
];

// Back-end form field: the visual e-mail/PDF designer
$GLOBALS['BE_FFL']['neoDesigner'] = DesignerWidget::class;
