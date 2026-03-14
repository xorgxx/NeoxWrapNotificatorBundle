<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class AttachmentsValidation extends Constraint
{
    public string $tooManyFilesMessage = 'Too many attachments.';

    public string $invalidFileMessage = 'Invalid attachment.';

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }

    public function validatedBy(): string
    {
        return AttachmentsValidationValidator::class;
    }
}
