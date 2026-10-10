<?php

declare(strict_types=1);

/*
 * Content element "neo_websitecheck". It deliberately uses only core columns of
 * tl_content (headline, text, url) so it adds no columns to a table that is
 * often close to the InnoDB row-size limit on large sites:
 *  - headline: title above the check box
 *  - text:     intro paragraph (what is checked, how long it takes)
 *  - url:      target of the "talk about the result" button, picked with the page
 *              picker or typed (fallback: contact page / brand.contact_url)
 *  - module:   the configuration (texts, e-mail, PDF) — the core integer column
 *              is re-labelled for this element type in ContentCallbacks::configField(),
 *              because tl_content must not grow (InnoDB row size on large sites)
 */
use Neo\WebsitecheckBundle\Backend\ContentCallbacks;

$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = [ContentCallbacks::class, 'configField'];

$GLOBALS['TL_DCA']['tl_content']['palettes']['neo_websitecheck'] =
    '{type_legend},type,headline;'
    .'{text_legend},text;'
    .'{config_legend},module;'
    .'{link_legend},url;'
    .'{template_legend:hide},customTpl;'
    .'{protected_legend:hide},protected;'
    .'{expert_legend:hide},cssID;'
    .'{invisible_legend:hide},invisible,start,stop';
