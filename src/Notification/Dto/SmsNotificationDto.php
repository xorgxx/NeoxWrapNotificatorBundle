<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Notification\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class SmsNotificationDto implements NotificationDtoInterface
{
    #[Assert\NotBlank]
    public string $to = '';

    #[Assert\NotBlank]
    public string $content = '';

    public function getChannel(): string
    {
        return 'sms';
    }
}
