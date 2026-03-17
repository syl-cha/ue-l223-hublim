<?php

namespace App\Form;

use App\Entity\Card;
use App\Entity\Category;
use App\Entity\Status;
use App\Entity\StudyField;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => "Titre de l'annonce",
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Ex. Recherche une colocation dans le centre de Limoges',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 7,
                    'placeholder' => 'Décris clairement ton besoin ou ton offre',
                ],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Catégorie',
                'placeholder' => 'Choisir une catégorie',
                'attr' => [
                    'class' => 'form-select category-select',
                ],
                'choice_attr' => function (Category $category) {
                    $color = $category->getParent() ? $category->getParent()->getColor() : null;
                    return $color ? ['data-color' => $color] : [];
                },
                'group_by' => function (Category $category) {
                    return $category->getParent() ? $category->getParent()->getName() : null;
                },
                'query_builder' => function ($repo) {
                    return $repo->createQueryBuilder('c')
                        ->leftJoin('c.parent', 'p')
                        ->addSelect('p')
                        ->where('c.parent IS NOT NULL')
                        ->orderBy('c.parent', 'ASC')
                        ->addOrderBy('c.name', 'ASC');
                },
            ])
            ->add('targetStatus', EntityType::class, [
                'class' => Status::class,
                'choice_label' => function (Status $status) {
                    return match ($status->getLabel()->value) {
                        'student' => 'Étudiant',
                        'teacher' => 'Enseignant',
                        'staff' => 'Personnel'
                    };
                },
                'label' => "À qui s'adresse cette annonce ?",
                'multiple' => true,
                'expanded' => true,
                'row_attr' => [
                    'class' => 'announce-check-group',
                ],
            ])
            ->add('targetStudyFields', EntityType::class, [
                'class' => StudyField::class,
                'choice_label' => 'name',
                'label' => 'Filières concernées',
                'multiple' => true,
                'expanded' => true,
                'query_builder' => function ($repo) {
                    return $repo->createQueryBuilder('s')
                        ->orderBy('s.type', 'ASC')
                        ->addOrderBy('s.department', 'ASC')
                        ->addOrderBy('s.name', 'ASC');
                },
                'row_attr' => ['class' => 'announce-check-group'],
            ])
            ->add('imageFiles', FileType::class, [
                'label'    => 'Photos (JPG, PNG, WebP — max 5MB chacune)',
                'multiple' => true,
                'mapped'   => false,
                'required' => false,
                'attr'     => [
                    'class'  => 'form-control',
                    'accept' => 'image/jpeg,image/png,image/webp'
                ],
                'constraints' => [
                    new All([
                        new File(
                            maxSize: '5M',
                            mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                            mimeTypesMessage: 'Formats acceptés : JPG, PNG, WebP.',
                        )
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Card::class,
        ]);
    }
}
