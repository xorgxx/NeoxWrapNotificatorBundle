<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Notification\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Neox\WrapNotificatorBundle\Validator\Constraints\AttachmentsValidation;

final class EmailNotificationDto implements NotificationDtoInterface
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $sender = '';

    #[Assert\NotBlank]
    #[Assert\Email]
    public string $recipient = '';

    #[Assert\NotBlank]
    public string $subject = '';

    public ?string $content = null;

    public bool $isHtml = true;

    public ?string $template = null;

    /**
     * @var array<string, mixed>
     */
    public array $templateVars = [];

    /**
     * @var array<int, \Symfony\Component\HttpFoundation\File\UploadedFile>
     */
    #[AttachmentsValidation]
    public array $attachments = [];

    public function getChannel(): string
    {
        return 'email';
    }
}
