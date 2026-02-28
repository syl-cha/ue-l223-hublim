<?php

namespace App\Controller;

use App\Entity\Status;
use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
  #[Route('/registration', name: 'app_registration')]
  // public function index(): Response
  // {
  //     return $this->render('registration/index.html.twig', [
  //         'controller_name' => 'RegistrationController',
  //     ]);
  // }
  public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
  {
    $user = new User();
    $form = $this->createForm(RegistrationFormType::class, $user);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      // Traitement des données transmisses lors de l'inscription
      // 1. Hash du mot de passe
      $user->setPassword(
        $userPasswordHasher->hashPassword($user, $form->get('plainPassword')->getData())
      );

      // 2. Récupération de l'entité Status correspondant à l'Enum choisi
      $statusLabel = $form->get('status')->getData();
      $status = $entityManager->getRepository(Status::class)->findOneBy(['label' => $statusLabel]);
      $user->setStatus($status);

      // 3. Initialisation des champs par défaut
      $user->setCreatedAt(new \DateTimeImmutable());
      $user->setIsVerified(false);
      $user->setTwoFactorSecret('none');

      // 4. Sauvegarde en base
      $entityManager->persist($user);
      $entityManager->flush();

      return $this->redirectToRoute('app_main');
    }

    return $this->render('registration/index.html.twig', [
      'registrationForm' => $form->createView(),
    ]);
  }
}
