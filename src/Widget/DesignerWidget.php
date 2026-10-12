<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Widget;

use Contao\Backend;
use Contao\StringUtil;
use Contao\System;
use Contao\Widget;
use Neo\WebsitecheckBundle\Design\BlockRegistry;
use Neo\WebsitecheckBundle\Design\Design;
use Neo\WebsitecheckBundle\Design\Presets;
use Neo\WebsitecheckBundle\Text\TextProvider;

/**
 * Back-end form field "neoDesigner": the visual designer for the e-mail
 * (eval.kind = mail) or the PDF report (eval.kind = pdf). The field stores the
 * design as JSON; this widget renders the container, embeds the block schema,
 * labels and endpoints for designer.js and validates the JSON on save.
 */
class DesignerWidget extends Widget
{
    protected $blnSubmitInput = true;

    protected $strTemplate = 'be_widget';

    public function __set($strKey, $varValue): void
    {
        if ('kind' === $strKey) {
            $this->arrConfiguration['kind'] = (string) $varValue;

            return;
        }

        parent::__set($strKey, $varValue);
    }

    public function generate(): string
    {
        $GLOBALS['TL_CSS']['neo_websitecheck_designer'] = 'bundles/neowebsitecheck/designer.css|static';
        $GLOBALS['TL_JAVASCRIPT']['neo_websitecheck_designer'] = 'bundles/neowebsitecheck/designer.js|static';

        $container = System::getContainer();
        $kind = BlockRegistry::KIND_MAIL === ($this->arrConfiguration['kind'] ?? '') ? BlockRegistry::KIND_MAIL : BlockRegistry::KIND_PDF;
        $locale = (string) ($container->get('request_stack')->getCurrentRequest()?->getLocale() ?: 'en');
        $registry = $container->get(BlockRegistry::class);
        $presets = $container->get(Presets::class);
        $translator = $container->get('translator');
        $value = \is_string($this->varValue) && '' !== trim($this->varValue) ? Design::normalise($this->varValue, $kind) : null;

        $ui = [];

        foreach (['blocks', 'structure', 'properties', 'theme', 'preview', 'presets', 'preset_confirm', 'add', 'duplicate', 'remove', 'up', 'down', 'empty', 'select_hint', 'placeholders', 'insert_placeholder', 'choose_image', 'upload_image', 'remove_image', 'pdf_preview', 'mail_preview', 'export_twig', 'export_confirm', 'twig_active', 'loading', 'error', 'bold', 'italic', 'link', 'link_prompt', 'list', 'drag', 'demo_hint', 'labels', 'labels_hint', 'mode_visual', 'mode_twig', 'mode_label', 'twig_intro', 'twig_generate', 'twig_generate_confirm', 'twig_leave_confirm', 'twig_error', 'twig_refresh'] as $key) {
            $ui[$key] = $translator->trans('designer.ui.'.$key, [], 'neo_websitecheck', $locale);
        }

        $base = Backend::addToUrl('', true, ['act', 'id', 'table', 'key']);
        $config = [
            'kind' => $kind,
            'field' => $this->strId,
            'recordId' => (int) ($this->activeRecord->id ?? 0),
            'language' => (string) ($this->activeRecord->language ?? ''),
            'languageField' => 'ctrl_language',
            'twigField' => 'ctrl_'.(BlockRegistry::KIND_MAIL === $kind ? 'mail_body' : 'pdf_body'),
            'isAdmin' => $container->get('security.helper')->isGranted('ROLE_ADMIN'),
            // a stored Twig source (advanced mode) is used instead of the visual design
            'twigActive' => '' !== trim((string) ($this->activeRecord->{BlockRegistry::KIND_MAIL === $kind ? 'mail_body' : 'pdf_body'} ?? '')),
            'endpoints' => [
                'preview' => $base.'&key=designer_preview',
                'pdf' => $base.'&key=designer_pdf',
                'preset' => $base.'&key=designer_preset',
                'upload' => $base.'&key=designer_upload',
                'twig' => $base.'&key=designer_twig',
            ],
            'requestToken' => $container->get('contao.csrf.token_manager')->getDefaultTokenValue(),
            'pickerUrl' => $container->get('contao.picker.builder')->getUrl('file', ['fieldType' => 'radio', 'filesOnly' => true, 'extensions' => 'jpg,jpeg,png,gif,webp,svg']),
            // UI labels in the back-end language, default words of new blocks in the configuration's language
            'schema' => $registry->schema($kind, $locale, (string) ($this->activeRecord->language ?? '')),
            'presets' => $presets->available($kind, $locale),
            'placeholders' => TextProvider::PLACEHOLDERS,
            'ui' => $ui,
        ];

        return sprintf(
            '<div class="nwd" id="nwd_%s" data-nwd>'
            .'<textarea name="%s" id="ctrl_%s" class="nwd-value" hidden>%s</textarea>'
            .'<script type="application/json" data-nwd-config>%s</script>'
            .'<noscript><p class="tl_info">JavaScript required.</p></noscript>'
            .'</div>',
            $this->strId,
            $this->strName,
            $this->strId,
            StringUtil::specialchars(null === $value ? '' : (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE)),
            // one broken byte in a text must not make the whole configuration unreadable (empty designer)
            str_replace('</', '<\/', (string) json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_INVALID_UTF8_SUBSTITUTE)),
        );
    }

    /**
     * Stores the normalised design (unknown blocks/properties dropped, HTML
     * sanitised) or NULL when the designer is empty.
     */
    protected function validator($varInput): mixed
    {
        $kind = BlockRegistry::KIND_MAIL === ($this->arrConfiguration['kind'] ?? '') ? BlockRegistry::KIND_MAIL : BlockRegistry::KIND_PDF;
        $raw = trim((string) $varInput);

        if ('' === $raw) {
            return null;
        }

        $design = Design::normalise($raw, $kind);

        if ([] === $design['blocks']) {
            return null;
        }

        return json_encode($design, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
