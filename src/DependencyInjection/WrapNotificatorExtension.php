<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class WrapNotificatorExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        // 1) Process bundle configuration
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Extract filters priority map from configuration and expose it as a parameter
        foreach ($config as $key => $value) {
            $container->setParameter('wrap_notificator.'.$key, $value);
        }


        // 2) Expose processed configuration as container parameters (do not override if already defined by app)
        //        $mercure = $config['mercure'] ?? [];
        //        $this->setParamIfAbsent($container, 'wrap_notificator.mercure.enabled', (bool)($mercure['enabled'] ?? true));
        //        $this->setParamIfAbsent($container, 'wrap_notificator.mercure.auto_inject', (bool)($mercure['auto_inject'] ?? false));
        //        $this->setParamIfAbsent($container, 'wrap_notificator.mercure.turbo_enabled', (bool)($mercure['turbo_enabled'] ?? false));
        //        $this->setParamIfAbsent($container, 'wrap_notificator.mercure.only_authenticated', (bool)($mercure['only_authenticated'] ?? false));
        //        $this->setParamIfAbsent($container, 'wrap_notificator.mercure.public_url', (string)($mercure['public_url'] ?? '%env(string:MERCURE_PUBLIC_URL)%'));
        //        $this->setParamIfAbsent($container, 'wrap_notificator.mercure.default_topics', (array)($mercure['default_topics'] ?? []));

        // 3) Load service definitions
        $locator = new FileLocator(__DIR__ . '/../Resources/config');
        $loader = new YamlFileLoader($container, $locator);
        $loader->load('services.yaml');
    }

    private function setParamIfAbsent(ContainerBuilder $container, string $name, mixed $value): void
    {
        if (!$container->hasParameter($name)) {
            $container->setParameter($name, $value);
        }
    }
}
