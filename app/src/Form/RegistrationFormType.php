<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\StudyField;
use App\Enum\StatusLabel;
use Symfony\Component\Form\AbstractType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Type de formulaire pour l'inscription d'un nouvel utilisateur.
 *
 * Ce formulaire gère la saisie des informations personnelles, de l'adresse e-mail,
 * du mot de passe, du statut et de la filière.
 */
class RegistrationFormType extends AbstractType
{
    /**
     * Construit le formulaire d'inscription avec ses différents champs et contraintes.
     *
     * @param FormBuilderInterface $builder Le constructeur de formulaire.
     * @param array<string, mixed> $options Les options du formulaire.
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir votre prénom.'),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir votre nom.'),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse email universitaire',
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir une adresse email.'),
                    new Regex(
                        pattern: '/@.*unilim\.fr$/i',
                        message: "Votre adresse email doit être une adresse de l'Université de Limoges",
                    ),
                    new Callback([$this, 'validateEmailStatusConsistency']),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'label' => 'Mot de passe',
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir un mot de passe'),
                    new Length(
                        min: 8,
                        minMessage: 'Votre mot de passe doit faire au moins {{ limit }} caractères',
                        // max length allowed by Symfony for security reasons
                        max: 4096,
                    ),
                ],
            ])
            ->add('status', EnumType::class, [
                'class' => StatusLabel::class,
                'label' => 'Votre statut',
                'mapped' => false,
                'choice_label' => function (StatusLabel $status) {
                    return match ($status) {
                        StatusLabel::STUDENT => 'Étudiant',
                        StatusLabel::TEACHER => 'Enseignant',
                        StatusLabel::STAFF => 'Personnel administratif',
                    };
                },
                'choice_attr' => function (StatusLabel $status) {
                    // Ajout d'un attribut de données pour identifier le choix STAFF côté JS
                    return ['data-status-type' => $status->value];
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

    /**
     * Configure les options par défaut pour ce type de formulaire.
     *
     * @param OptionsResolver $resolver Le résolveur d'options.
     *
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }

    /**
     *  Vérifie la cohérence entre l'adresse email fournie et le statut mentionné :
     *
     * - Si le statut est Étudiant (`STUDENT`), l'e-mail doit se terminer par `@etu.unilim.fr`.
     * - Si le statut est Enseignant (`TEACHER`) ou Personnel (`STAFF`), l'e-mail doit se terminer par `@unilim.fr` et non `@etu.unilim.fr`.
     *
     * @param string $email adresse
     * @param ExecutionContextInterface $context contexte du formulaire pour récupérer les valeurs à la volée
     * @return void
     */
    public function validateEmailStatusConsistency(?string $email, ExecutionContextInterface $context): void
    {
        if (!$email) {
            return;
        }

        $form = $context->getRoot();
        $status = $form->get('status')->getData();

        if ($status === StatusLabel::STUDENT) {
            if (!preg_match('/@etu\.unilim\.fr$/i', $email)) {
                $context->buildViolation("L'adresse email d'un étudiant doit se terminer par @etu.unilim.fr")
                    ->addViolation();
            }
        } elseif ($status === StatusLabel::TEACHER || $status === StatusLabel::STAFF) {
            if (!preg_match('/@unilim\.fr$/i', $email) || preg_match('/@etu\.unilim\.fr$/i', $email)) {
                $context->buildViolation("L'adresse email pour ce statut doit se terminer par @unilim.fr et non @etu.unilim.fr")
                    ->addViolation();
            }
        }
    }
}
