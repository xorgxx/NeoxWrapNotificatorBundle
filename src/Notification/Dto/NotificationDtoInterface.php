<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Notification\Dto;

interface NotificationDtoInterface
{
    public function getChannel(): string;
}
