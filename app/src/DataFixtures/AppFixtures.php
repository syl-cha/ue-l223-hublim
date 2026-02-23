<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Card;
use App\Entity\Status;
use App\Entity\Category;
use App\Entity\StudyField;
use App\Enum\StatusLabel; // N'oublie pas l'import de ton Enum
use App\Enum\CardState;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $hasher;
    public function __construct(UserPasswordHasherInterface $hasher) { $this->hasher = $hasher; }

    public function load(ObjectManager $manager): void
    {
        // 1. On crée la Catégorie (Besoin de : name, slug)
      $category = new Category();
      $category->setName('Informatique');
      $category->setSlug('informatique');
      $manager->persist($category);

      // 2. On crée le Statut (Besoin de : label)
      $status = new Status();
      $status->setLabel(StatusLabel::STUDENT);
      $manager->persist($status);

      // 3. On crée le domaine d'étude (Besoin de : name, type, theme)
      // C'EST ICI QUE SE TROUVAIT L'ERREUR "TYPE"
      $studyField = new StudyField();
      $studyField->setName('BTS SIO');
      $studyField->setType('Formation Initiale'); // Ajout du type obligatoire
      $studyField->setTheme('Développement');      // Ajout du theme obligatoire
      $manager->persist($studyField);

      // 4. On crée l'utilisateur
      $user = new User();
      $user->setEmail('jeanne.salvadori@etu.unilim.fr');
      $user->setFirstName('Jeanne');
      $user->setLastName('Salvadori');
      $user->setPassword($this->hasher->hashPassword($user, 'password'));
      $user->setStatus($status);
      $user->setStudyField($studyField);
      $user->setCreatedAt(new \DateTimeImmutable());
      $user->setIsVerified(true);
      $user->setTwoFactorSecret('none');
      $manager->persist($user);

      // 5. On crée la carte
      $card = new Card();
      $card->setTitle('Titre de la carte');
      $card->setDescription('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');
      $card->setState(CardState::DRAFT);
      $card->setCreatedAt(new \DateTimeImmutable());
      $card->setUser($user);
      $card->setCategory($category);
      $manager->persist($card);

      // 6. On valide tout
      $manager->flush();
    }
}