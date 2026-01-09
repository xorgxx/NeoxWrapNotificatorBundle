<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Notification\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ChatNotificationDto implements NotificationDtoInterface
{
    #[Assert\NotBlank]
    public string $transport = '';

    #[Assert\NotBlank]
    public string $content = '';

    public ?string $subject = null;

    /**
     * @var array<string, mixed>
     */
    public array $options = [];

    public function getChannel(): string
    {
        return 'chat';
    }
}
