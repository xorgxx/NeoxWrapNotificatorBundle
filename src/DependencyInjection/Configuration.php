<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('wrap_notificator');
        $rootNode = $treeBuilder->getRootNode();
        /** @var \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $rootNode */

        /** @var \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $rootNode */
        $children = $rootNode->children();
        /** @var \Symfony\Component\Config\Definition\Builder\NodeBuilder $children */

        $mercure = $children->arrayNode('mercure');
        /** @var \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $mercure */
        $mercure->addDefaultsIfNotSet();
        $mercureChildren = $mercure->children();
        if (!$mercureChildren instanceof \Symfony\Component\Config\Definition\Builder\NodeBuilder) {
            // Fallback for static analysis edge case
            return $treeBuilder;
        }

        // @phpstan-ignore-next-line
        $mercureChildren
            ->booleanNode('enabled')->defaultTrue()->end()
            ->booleanNode('notify_status')->defaultFalse()->end()
            ->booleanNode('auto_inject')->defaultFalse()->end()
            ->booleanNode('turbo_enabled')->defaultFalse()->end()
            ->booleanNode('only_authenticated')->defaultFalse()->end()
            // Default to env var if not explicitly configured in YAML
            ->scalarNode('public_url')->defaultValue('%env(string:MERCURE_PUBLIC_URL)%')->end()
            ->booleanNode('with_credentials_default')->defaultFalse()->end()
            ->arrayNode('default_topics')
                ->prototype('scalar')->end()
                ->defaultValue(['geo_notificator/stream'])
            ->end()
            ->arrayNode('ui')
                ->addDefaultsIfNotSet()
                ->children()
                    ->booleanNode('external_css')->defaultTrue()->end()
                    ->booleanNode('auto_link_css')->defaultTrue()->end()
                    ->scalarNode('renderer')->defaultValue('auto')->end()
                    ->scalarNode('force_theme')->defaultValue('auto')->end()
                    ->scalarNode('toast_theme')->defaultValue('default')->end()
                    ->scalarNode('asset_path')->defaultValue('@WrapNotificator/css/wrap_notificator.css')->end()
                    ->scalarNode('asset_fallback_prefix')->defaultValue('/bundles/wrapnotificator')->end()
                ->end()
            ->end()
        ->end();

        $children->arrayNode('logging')
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('enabled')->defaultFalse()->end()
            ->end()
        ->end();

        $children->arrayNode('default_recipients')
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('email')->defaultNull()->end()
                ->scalarNode('sms')->defaultNull()->end()
                ->scalarNode('chat')->defaultNull()->end()
            ->end()
        ->end();

        $children->arrayNode('default_senders')
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('email')->defaultNull()->end()
                ->scalarNode('sms')->defaultNull()->end()
            ->end()
        ->end();

        $children->arrayNode('email_template')
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('enabled')->defaultTrue()->end()
                ->scalarNode('template')->defaultValue('@WrapNotificator/email/contact_form.html.twig')->end()
            ->end()
        ->end();

        $children->arrayNode('attachments_validation')
            ->addDefaultsIfNotSet()
            ->children()
                ->integerNode('max_files')->defaultValue(5)->min(0)->end()
                ->scalarNode('max_size')->defaultValue('1M')->end()
                ->arrayNode('mime_types')
                    ->prototype('scalar')->end()
                    ->defaultValue([
                        'application/pdf',
                        'image/png',
                        'image/jpeg',
                        'image/gif',
                        'text/plain',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                ->end()
            ->end()
        ->end();

        $children->arrayNode('live_flash')
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('enabled')->defaultFalse()->end()
                ->booleanNode('consume')->defaultTrue()->end()
                ->booleanNode('group_messages')->defaultFalse()->end()
                ->scalarNode('default_topic_prefix')->defaultValue('wrap_notificator/flash')->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
