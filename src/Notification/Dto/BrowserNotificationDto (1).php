<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Notification\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class BrowserNotificationDto implements NotificationDtoInterface
{
    #[Assert\NotBlank]
    public string $topic = '';

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public function getChannel(): string
    {
        return 'browser';
    }
}
