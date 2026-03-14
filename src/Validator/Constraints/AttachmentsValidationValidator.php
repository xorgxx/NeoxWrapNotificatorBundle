<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Validator\Constraints;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AttachmentsValidationValidator extends ConstraintValidator
{
    /** @param array<string, mixed> $config */
    public function __construct(
        private readonly array $config,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof AttachmentsValidation) {
            return;
        }

        if ($value === null || $value === []) {
            return;
        }

        if (!is_array($value)) {
            $this->context->buildViolation($constraint->invalidFileMessage)->addViolation();
            return;
        }

        $maxFiles = (int)($this->config['max_files'] ?? 0);
        if ($maxFiles > 0 && count($value) > $maxFiles) {
            $this->context->buildViolation($constraint->tooManyFilesMessage)->addViolation();
            return;
        }

        $fileConstraint = new File([
            'maxSize' => (string)($this->config['max_size'] ?? '1M'),
            'mimeTypes' => (array)($this->config['mime_types'] ?? []),
        ]);

        foreach ($value as $item) {
            if (!$item instanceof UploadedFile) {
                $this->context->buildViolation($constraint->invalidFileMessage)->addViolation();
                continue;
            }

            $violations = $this->validator->validate($item, $fileConstraint);
            foreach ($violations as $violation) {
                $this->context->buildViolation($violation->getMessage())
                    ->addViolation();
            }
        }
    }
}
