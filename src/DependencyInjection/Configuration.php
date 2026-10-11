<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Semantic configuration ("neo_websitecheck:" in config/config.yaml). Every key
 * has a working default so a fresh installation runs out of the box; API keys
 * are the only things an operator must add (see README).
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('neo_websitecheck');
        $root = $treeBuilder->getRootNode();

        $root
            ->children()
                ->arrayNode('pagespeed')
                    ->addDefaultsIfNotSet()
                    ->info('Google PageSpeed Insights API (free key from Google Cloud Console).')
                    ->children()
                        ->scalarNode('api_key')->defaultValue('')->end()
                        ->integerNode('timeout')->defaultValue(55)->min(20)->max(170)->end()
                    ->end()
                ->end()
                ->arrayNode('ai')
                    ->addDefaultsIfNotSet()
                    ->info('OpenAI-compatible chat-completions endpoint for the "read like a visitor" review. Leave api_key empty to skip the AI step.')
                    ->children()
                        ->scalarNode('base_url')->defaultValue('https://router.requesty.ai/v1')->end()
                        ->scalarNode('api_key')->defaultValue('')->end()
                        ->scalarNode('model')->defaultValue('anthropic/claude-sonnet-4-5')->end()
                        ->integerNode('max_tokens')->defaultValue(3000)->min(500)->info('Upper limit of the answer; the extra sections need room.')->end()
                        ->integerNode('timeout')->defaultValue(90)->min(20)->max(170)->end()
                        ->booleanNode('send_screenshot')->defaultTrue()->info('Attach the mobile screenshot of the start page to the AI request (needs a vision model).')->end()
                    ->end()
                ->end()
                ->arrayNode('mail')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('from_email')->defaultValue('')->info('Sender address; empty = Contao admin e-mail.')->end()
                        ->scalarNode('from_name')->defaultValue('')->end()
                        ->scalarNode('reply_to')->defaultValue('')->end()
                        ->scalarNode('notify_email')->defaultValue('')->info('Operator copy of every report, including the lead data (name, e-mail, newsletter choice). Empty = no copy.')->end()
                    ->end()
                ->end()
                ->arrayNode('brand')
                    ->addDefaultsIfNotSet()
                    ->info('Used in the report, the e-mail and the result box. Everything is plain text; the templates can be overridden in templates/.')
                    ->children()
                        ->scalarNode('name')->defaultValue('')->info('Company / agency name; empty = Contao website title.')->end()
                        ->scalarNode('owner_name')->defaultValue('')->info('The person who signs the report, e.g. "Erich Nigg".')->end()
                        ->scalarNode('phone')->defaultValue('')->end()
                        ->scalarNode('website_url')->defaultValue('')->end()
                        ->scalarNode('contact_url')->defaultValue('')->info('Fallback link for the "talk about it" button when the element has no redirect page.')->end()
                        ->scalarNode('privacy_url')->defaultValue('')->info('Link to the privacy policy shown under the lead form.')->end()
                        ->scalarNode('logo')->defaultValue('')->info('PNG/JPG logo path relative to the project dir (e.g. files/theme/logo.png) for PDF and e-mail.')->end()
                        ->scalarNode('logo_dark')->defaultValue('')->info('Logo for dark backgrounds (e-mail header, dark cover), relative to the project dir.')->end()
                        ->scalarNode('portrait')->defaultValue('')->info('Optional portrait photo path for the "what happens next" box.')->end()
                        ->scalarNode('color_primary')->defaultValue('#1f2328')->end()
                        ->scalarNode('color_accent')->defaultValue('#0969da')->end()
                    ->end()
                ->end()
                ->arrayNode('limits')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('checks_per_ip_per_hour')->defaultValue(2)->min(1)->info('Checks one visitor (IP address) may start per hour.')->end()
                        ->integerNode('checks_per_ip_per_day')->defaultValue(10)->min(1)->info('Checks one visitor (IP address) may start per day.')->end()
                        ->integerNode('cache_hours')->defaultValue(24)->min(0)->info('A second check of the same domain within this window reuses the stored result.')->end()
                        ->integerNode('max_pages')->defaultValue(5)->min(1)->max(8)->end()
                        ->integerNode('retention_days')->defaultValue(90)->min(1)->info('Checks (including lead data and screenshots) are deleted after this many days by the daily cron job.')->end()
                    ->end()
                ->end()
                ->arrayNode('pdf')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->enumNode('engine')->values(['dompdf', 'gotenberg', 'none'])->defaultValue('dompdf')->end()
                        ->scalarNode('gotenberg_url')->defaultValue('')->info('e.g. http://gotenberg:3000 — Chromium rendering, pixel-identical to the HTML template.')->end()
                    ->end()
                ->end()
                ->arrayNode('newsletter')
                    ->addDefaultsIfNotSet()
                    ->info('Optional double-opt-in subscription when the visitor ticks the newsletter box.')
                    ->children()
                        ->enumNode('provider')->values(['none', 'brevo'])->defaultValue('none')->end()
                        ->scalarNode('label')->defaultValue('')->info('Checkbox text; empty = translated default.')->end()
                        ->scalarNode('brevo_api_key')->defaultValue('')->end()
                        ->integerNode('brevo_list_id')->defaultValue(0)->end()
                        ->integerNode('brevo_template_id')->defaultValue(0)->info('Brevo double-opt-in template id.')->end()
                        ->scalarNode('brevo_redirect_url')->defaultValue('')->info('Page the visitor lands on after confirming the double opt-in.')->end()
                    ->end()
                ->end()
                ->arrayNode('tracking')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('data_layer')->defaultTrue()->info('Push check_start / check_gate / check_lead / check_result / check_abandon into window.dataLayer (GTM). A DOM event "neo_websitecheck" is always dispatched.')->end()
                    ->end()
                ->end()
                ->scalarNode('storage_dir')->defaultValue('%kernel.project_dir%/var/neo-websitecheck')->info('Screenshots and PDFs are stored here (outside the web root).')->end()
                ->scalarNode('user_agent')->defaultValue('Mozilla/5.0 (compatible; NeoWebsitecheck/1.0; +https://github.com/NEO-Digital-AT/NEO-Contao-Websitecheck)')->end()
            ->end();

        return $treeBuilder;
    }
}
