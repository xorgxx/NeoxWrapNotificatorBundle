<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Minishlink\WebPush\WebPush;
use Psr\Cache\CacheItemPoolInterface;
use Neox\WrapNotificatorBundle\Command\DiagnoseCommand;
use Neox\WrapNotificatorBundle\Command\NotifyCommand;
use Neox\WrapNotificatorBundle\Contract\DedupeRepositoryInterface;
use Neox\WrapNotificatorBundle\Contract\SenderInterface;
use Neox\WrapNotificatorBundle\Infrastructure\Dedupe\CacheDedupeRepository;
use Neox\WrapNotificatorBundle\Infrastructure\Dedupe\InMemoryDedupeRepository;
use Neox\WrapNotificatorBundle\Notification\MessageFactory;
use Neox\WrapNotificatorBundle\Notification\TypedSender;
use Neox\WrapNotificatorBundle\Service\NotifierFacade;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();
    $services->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(MessageFactory::class);

    $services->set(TypedSender::class)
        ->arg('$mailer', service('?mailer'))
        ->arg('$chatter', service('?chatter'))
        ->arg('$texter', service('?texter'))
        ->arg('$hub', service('?mercure.hub.default'))
        ->arg('$webPush', service('?'.WebPush::class));

    // Dedupe repositories
    $services->set(InMemoryDedupeRepository::class);

    $services->set(CacheDedupeRepository::class)
        ->arg('$cache', service('?cache.app'));

    // Default alias to Cache (falls back to in-memory internally if cache.app is missing)
    $services->alias(DedupeRepositoryInterface::class, CacheDedupeRepository::class);

    $services->set(NotifierFacade::class)
        ->arg('$dedupe', service('?'.DedupeRepositoryInterface::class));

    // Alias interface to implementation
    $services->alias(SenderInterface::class, TypedSender::class);

    $services->set(NotifyCommand::class)
        ->tag('console.command');

    $services->set(DiagnoseCommand::class)
        ->arg('$hub', service('?mercure.hub.default'))
        ->arg('$bus', service('?messenger.bus.default'))
        ->tag('console.command');
};
