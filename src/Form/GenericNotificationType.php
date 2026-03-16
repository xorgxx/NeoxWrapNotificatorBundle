<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Form;

//use Karser\Recaptcha3Bundle\Form\Type\Recaptcha3Type;
use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class GenericNotificationType extends AbstractType
{
    /**
     * @throws \ReflectionException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $data = $options['data'] ?? null;
        if ($data === null) {
            return;
        }

        $builder->setCompound(true);
        $excludeFields = $options['exclude_fields'] ?? [];
        $reflection = new \ReflectionClass($data);
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();

            if (in_array($name, $excludeFields, true)) {
                continue;
            }

            $type = $property->getType();
            $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;

            // Special handling for attachments field
            if ($name === 'attachments') {
                $formType = FileType::class;
            } else {
                $formType = match ($typeName) {
                    'bool' => CheckboxType::class,
                    'int', 'float' => NumberType::class,
                    'array' => TextareaType::class, // Simplified for dynamic forms, could be improved
                    default => ($name === 'content' || $name === 'data') ? TextareaType::class : TextType::class,
                };
            }

            $fieldOptions = [
                'label' => 'contact.form.' . $name,
                'required' => !$type?->allowsNull(),
                'property_path' => $name,
                'translation_domain' => 'messages',
                'attr' => [
                    'class' => $formType === CheckboxType::class ? 'form-check-input' : 'form-control',
                    'placeholder' => 'contact.placeholder.' . $name,
                ],
            ];

            if ($formType === TextareaType::class) {
                $fieldOptions['attr']['rows'] = 4;
            }

            if ($formType === TextareaType::class && $typeName === 'array') {
                $fieldOptions['getter'] = fn ($object) => json_encode($object->$name);
                $fieldOptions['setter'] = function (&$object, $value) use ($name) {
                    $object->$name = json_decode($value, true) ?: [];
                };
            }

            if ($formType === CheckboxType::class) {
                $fieldOptions['required'] = false;
            }

            if ($name === 'attachments') {
                $fieldOptions['multiple'] = true;
                $fieldOptions['required'] = false;
                $fieldOptions['attr']['accept'] = '.pdf,.png,.jpg,.jpeg,.gif,.txt,.doc,.docx,.xls,.xlsx';
            }

            $builder->add($name, $formType, $fieldOptions);
        }

        if (!in_array('fax', $excludeFields, true)) {
            $builder->add('fax', HiddenType::class, [
                'mapped' => false,
                'required' => false,
                'label' => false,
                'translation_domain' => 'messages',
                'attr' => [
                    'class' => 'wrap-notificator-hp',
                    'autocomplete' => 'off',
                    'tabindex' => '-1',
                ],
            ]);
        }

        $recaptchaType = null;

        if (class_exists(\Karser\Recaptcha3Bundle\Form\Recaptcha3Type::class)) {
            $recaptchaType = \Karser\Recaptcha3Bundle\Form\Recaptcha3Type::class;
        } elseif (class_exists(\Karser\Recaptcha3Bundle\Form\Type\Recaptcha3Type::class)) {
            $recaptchaType = \Karser\Recaptcha3Bundle\Form\Type\Recaptcha3Type::class;
        }

        if ($recaptchaType && !in_array('captcha', $excludeFields, true)) {
            $builder->add('captcha', $recaptchaType, [
                'mapped' => false,
                'label' => false,
                'required' => false,
                'constraints' => [new Recaptcha3()],
                'action_name' => 'wrap_notificator_form',
            ]);
        }

        if (!in_array('send', $excludeFields, true)) {
            $builder->add('send', SubmitType::class, [
                'label' => 'contact.form.send',
                'translation_domain' => 'messages',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_protection' => false,
            'exclude_fields' => [],
        ]);

        $resolver->setNormalizer('data_class', function (OptionsResolver $resolver, $value) {
            $data = $resolver['data'] ?? null;
            if ($value === null && is_object($data)) {
                return get_class($data);
            }
            return $value;
        });
    }
}
