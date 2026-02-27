<?php

namespace App\Form;

use App\Entity\Status;
use App\Entity\StudyField;
use App\Entity\User;
use App\Enum\StatusLabel;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir votre prénom.']),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir votre nom.']),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse email universitaire',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir une adresse email.']),
                    new Regex([
                        'pattern' => '/@.*unilim\.fr$/i',
                        'message' => "Votre adresse email doit être une adresse de l'Université de Limoges",
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'label' => 'Mot de passe',
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un mot de passe',
                    ]),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Votre mot de passe doit faire au moins {{ limit }} caractères',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('status', EntityType::class, [
                'class' => Status::class,
                'label' => 'Votre statut',
                'choice_label' => function (Status $status) {
                    return match ($status->getLabel()) {
                        StatusLabel::STUDENT => 'Étudiant',
                        StatusLabel::TEACHER => 'Enseignant',
                        StatusLabel::STAFF => 'Personnel administratif',
                        default => $status->getLabel()?->value,
                    };
                },
                'choice_attr' => function(Status $status) {
                    // Ajout d'un attribut de données pour identifier le choix STAFF côté JS
                    return ['data-status-type' => $status->getLabel()?->value];
                },
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('studyField', EntityType::class, [
                'class' => StudyField::class,
                'label' => 'Votre filière / domaine',
                'choice_label' => 'name',
                'required' => false, // Deviendra obligatoire dynamiquement ou via validation personnalisée, géré en JS
                'placeholder' => 'Sélectionnez votre filière...',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
