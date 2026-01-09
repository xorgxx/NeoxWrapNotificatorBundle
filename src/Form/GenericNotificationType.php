<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Form;

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

        $reflection = new \ReflectionClass($data);
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();
            $type = $property->getType();
            $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;

            $formType = match ($typeName) {
                'bool' => CheckboxType::class,
                'int', 'float' => NumberType::class,
                'array' => TextareaType::class, // Simplified for dynamic forms, could be improved
                default => ($name === 'content' || $name === 'data') ? TextareaType::class : TextType::class,
            };

            $fieldOptions = [
                'required' => !$type?->allowsNull(),
            ];

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

        $builder->add('send', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
