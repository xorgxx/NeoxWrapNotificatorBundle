<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Notification\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class EmailNotificationDto implements NotificationDtoInterface
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $to = '';

    #[Assert\NotBlank]
    public string $subject = '';

    public ?string $content = null;

    public bool $isHtml = true;

    public ?string $template = null;

    /**
     * @var array<string, mixed>
     */
    public array $templateVars = [];

    public function getChannel(): string
    {
        return 'email';
    }
}
