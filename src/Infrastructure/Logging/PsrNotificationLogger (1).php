<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Infrastructure\Logging;

use Neox\WrapNotificatorBundle\Contract\NotificationLoggerInterface;
use Neox\WrapNotificatorBundle\Notification\DeliveryStatus;
use Psr\Log\LoggerInterface;

final readonly class PsrNotificationLogger implements NotificationLoggerInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function log(DeliveryStatus $status): void
    {
        $context = $status->toArray();
        $message = sprintf(
            '[WrapNotificator] [%s] [%s] %s',
            strtoupper($status->channel),
            strtoupper($status->status),
            $status->message ?? ''
        );

        if ($status->status === DeliveryStatus::STATUS_FAILED) {
            $this->logger->error($message, $context);
        } else {
            $this->logger->info($message, $context);
        }
    }
}
