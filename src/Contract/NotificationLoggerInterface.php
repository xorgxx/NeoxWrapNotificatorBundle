<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Contract;

use Neox\WrapNotificatorBundle\Notification\DeliveryStatus;

interface NotificationLoggerInterface
{
    public function log(DeliveryStatus $status): void;
}
