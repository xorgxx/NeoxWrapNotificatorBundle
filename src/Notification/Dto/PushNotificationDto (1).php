<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Notification\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class PushNotificationDto implements NotificationDtoInterface
{
    /**
     * @var array{endpoint: string, keys: array{p256dh: string, auth: string}}
     */
    #[Assert\NotBlank]
    public array $subscription;

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public ?int $ttl = null;

    public function getChannel(): string
    {
        return 'push';
    }
}
