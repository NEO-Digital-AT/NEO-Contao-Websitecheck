<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Loads the service definitions and exposes the processed configuration as the
 * "neo_websitecheck.config" parameter (consumed by the Settings service).
 */
final class NeoWebsitecheckExtension extends Extension
{
    public function getAlias(): string
    {
        return 'neo_websitecheck';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);
        $container->setParameter('neo_websitecheck.config', $config);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.yaml');
    }
}
