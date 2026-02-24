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
use Faker\Factory;

class AppFixtures extends Fixture
{
    private $hasher;
    public function __construct(UserPasswordHasherInterface $hasher) { 
      $this->hasher = $hasher; 
    }

    public function load(ObjectManager $manager): void
    {
      //On initialise Faker en français
      $faker = Factory::create('fr_FR');

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
      $users = [];
      for ($i = 0; $i < 5; $i++){
        //On définit le nom d'abord pour pouvoir réutiliser les variables dans l'email
        $firstName = $faker->firstName();
        $lastName  = $faker->lastName();

        //On crée l'utilisateur
        $user = new User();
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setEmail(strtolower($firstName . '.' . $lastName) . '@etu.unilim.fr');
        $user->setPassword($this->hasher->hashPassword($user, 'password'));
        $user->setStatus($status);
        $user->setStudyField($studyField);
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setIsVerified(true);
        $user->setTwoFactorSecret('none');
        $manager->persist($user);
        $users[] = $user;
      }
      

      // 5. On crée les cartes
      for ($i=0; $i < 20; $i++){
        $card = new Card();
        $card->setTitle($faker->sentence(6, true));
        $card->setDescription($faker->paragraphs(3, true));
        $card->setState(CardState::DRAFT);
        $card->setCreatedAt(new \DateTimeImmutable());
        $card->setUser($faker->randomElement($users));
        $card->setCategory($category);
        $manager->persist($card);
      }
      
      // 6. On valide tout
      $manager->flush();
    }
}