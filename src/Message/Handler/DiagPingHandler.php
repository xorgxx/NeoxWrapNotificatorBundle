<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Message\Handler;

use Neox\WrapNotificatorBundle\Command\DiagPing;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class DiagPingHandler
{
    public function __invoke(DiagPing $message): string
    {
        return 'pong';
    }
}
