<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Form;

use Karser\Recaptcha3Bundle\Form\Type\Recaptcha3Type;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
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

            $formType = match ($typeName) {
                'bool' => CheckboxType::class,
                'int', 'float' => NumberType::class,
                'array' => TextareaType::class, // Simplified for dynamic forms, could be improved
                default => ($name === 'content' || $name === 'data') ? TextareaType::class : TextType::class,
            };

            $fieldOptions = [
                'label' => 'contact.form.' . $name,
                'required' => !$type?->allowsNull(),
                'property_path' => $name,
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

            $builder->add($name, $formType, $fieldOptions);
        }

        if (!in_array('captcha', $excludeFields, true) && class_exists(Recaptcha3Type::class)) {
            $builder->add('captcha', Recaptcha3Type::class, [
                'mapped' => false,
                'constraints' => [new Recaptcha3()],
            ]);
        }

        if (!in_array('send', $excludeFields, true)) {
            $builder->add('send', SubmitType::class);
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
