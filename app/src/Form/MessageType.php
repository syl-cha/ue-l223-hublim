<?php

namespace App\Form;

use App\Entity\Message;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire de création et d'édition d'un message.
 */
class MessageType extends AbstractType
{
    /**
     * Construit le formulaire pour les messages.
     *
     * @param FormBuilderInterface $builder Le constructeur de formulaire.
     * @param array<string, mixed> $options Les options du formulaire.
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => 'Votre message',
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Écrivez votre message ici...',
                ],
            ]);
    }

    /**
     * Configure les options par défaut pour le formulaire de message.
     *
     * @param OptionsResolver $resolver Le résolveur d'options.
     *
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Message::class,
        ]);
    }
}
