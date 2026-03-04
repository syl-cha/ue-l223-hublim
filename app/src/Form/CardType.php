<?php

namespace App\Form;

use App\Entity\Card;
use App\Entity\Category;
use App\Entity\Status;
use App\Entity\StudyField;
use App\Enum\CardState;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('description')
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
            ])
            ->add('targetStatus', EntityType::class, [
                'class' => Status::class,
                'choice_label' => function (Status $status) {
                    return $status->getLabel()->value; // Récupère la valeur de l'Enum
                },
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('targetStudyFields', EntityType::class, [
                'class' => StudyField::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
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
