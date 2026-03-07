<?php
//  voir doc : https://symfony.com/doc/current/security/user_checkers.html#creating-a-custom-user-checker
namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
  public function checkPreAuth(UserInterface $user): void
  {
    if (!$user instanceof User) {
      return;
    }

    // Si l'utilisateur n'a pas vérifié son email, on bloque la connexion
    if (!$user->isVerified()) {
      throw new CustomUserMessageAccountStatusException(
        'Vous devez d\'abord vérifier votre adresse email universitaire pour vous connecter. Consultez votre boîte de réception.'
      );
    }
  }

  public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
  {
    // Cette méthode est obligatoire et utilisée pour faire quelque chose après....
  }
}
