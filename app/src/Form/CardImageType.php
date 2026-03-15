<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;

class CardImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('imageFiles', FileType::class, [
            'label'    => 'Photos (JPG, PNG, WebP — max 5MB chacune)',
            'multiple' => true,
            'mapped'   => false,
            'required' => false,
            'attr'     => ['accept' => 'image/jpeg,image/png,image/webp'],
            'constraints' => [
                new All([
                    new File(
                        maxSize: '5M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        mimeTypesMessage: 'Formats acceptés : JPG, PNG, WebP.',
                    )
                ])
            ],
        ]);
    }
}