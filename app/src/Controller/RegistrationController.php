<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Status;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\AppCustomAuthenticator;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
  public function __construct(private EmailVerifier $emailVerifier) {}

  #[Route('/register', name: 'app_register')]
  public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager): Response
  {
    $user = new User();
    $form = $this->createForm(RegistrationFormType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      /** @var string $plainPassword */
      $plainPassword = $form->get('plainPassword')->getData();

      // encode the plain password
      $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

      // default values
      $user->setCreatedAt(new \DateTimeImmutable());
      $user->setTwoFactorSecret('none');
      // Cas particulier su statut (car il y a 'mapped' => false dans le fichier RegistrationFormType.php)
      // 1. On va chercher ce que l'utilisateur a coché (qui était "non-mappé")
      $statusLabel = $form->get('status')->getData();
      // 2. On cherche le "vrai" statut correspondant dans la base de données
      $status = $entityManager->getRepository(Status::class)->findOneBy(['label' => $statusLabel]);
      // 3. On l'affecte "à la main" à notre utilisateur
      $user->setStatus($status);

      $entityManager->persist($user);
      $entityManager->flush();

      // generate a signed url and email it to the user
      $this->emailVerifier->sendEmailConfirmation(
        'app_verify_email',
        $user,
        (new TemplatedEmail())
          ->from(new Address('inscription@hublim.bradype.fr', 'Hublim Mail Verification Bot'))
          ->to((string) $user->getEmail())
          ->subject('Please Confirm your Email')
          ->htmlTemplate('registration/confirmation_email.html.twig')
      );

      // do anything else you need here, like send an email

      // return $security->login($user, AppCustomAuthenticator::class, 'main');

      // On prévient l'utilisateur qu'il doit aller voir ses mails
      $this->addFlash('success', "Félicitations ! Votre compte a été créé avec succès !<br> Veuillez vérifier votre boîte mail pour l'adresse <strong>" . $user->getEmail() .
        "</strong> pour activer votre compte avant de vous connecter.");

      // On le redirige vers la page de connexion 
      return $this->redirectToRoute('app_login');
    }

    return $this->render('registration/register.html.twig', [
      'registrationForm' => $form,
    ]);
  }

  #[Route('/verify/email', name: 'app_verify_email')]
  public function verifyUserEmail(Request $request, TranslatorInterface $translator, UserRepository $userRepository): Response
  {
    $id = $request->query->get('id');

    if (null === $id) {
      return $this->redirectToRoute('app_register');
    }

    $user = $userRepository->find($id);

    if (null === $user) {
      return $this->redirectToRoute('app_register');
    }

    // validate email confirmation link, sets User::isVerified=true and persists
    try {
      $this->emailVerifier->handleEmailConfirmation($request, $user);
    } catch (VerifyEmailExceptionInterface $exception) {
      $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

      return $this->redirectToRoute('app_register');
    }

    $this->addFlash('success', 'Votre adresse email a bien été vérifiée. Bienvenue !');

    return $this->redirectToRoute('app_card_index');
  }
}
