<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Card;
use App\Entity\Status;
use App\Entity\Category;
use App\Entity\StudyField;
use App\Entity\Image;
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

      //On charge les catégories depuis le json
      $categoryData = json_decode(file_get_contents(__DIR__ . '/data/categories.json'), true);

      // 1. On crée les Catégorie (Besoin de : name, slug)
      $categories = [];
      $sousCategories = [];

      foreach ($categoryData as $c){
        $category = new Category();
        $category -> setName($c['nom']);
        $category -> setSlug($c['slug']);
        $manager  -> persist($category);
        $categories[] = $category;

        if (isset($c['sous_categories'])) {
          foreach ($c['sous_categories'] as $s_c){
            $sousCategory = new Category();
            $sousCategory -> setName($s_c['nom']);
            $sousCategory -> setSlug($s_c['slug']);
            $sousCategory -> setParent($category);

            $manager->persist($sousCategory);
            $sousCategories [] = $sousCategory;
          }
        }
      }

      // 2. On crée le Statut (Besoin de : label)
      $status = new Status();
      $status->setLabel(StatusLabel::STUDENT);
      $manager->persist($status);

      // 3. On crée le domaine d'étude (Besoin de : name, type, theme)
      //On charge les filières depuis le json
      $filiereData = json_decode(file_get_contents(__DIR__ . '/data/filieres.json'), true);

      $filieres = [];

      foreach ($filiereData as $f){
        $studyField = new StudyField();
        $studyField -> setName($f['nom']);
        $studyField -> setType($f['type']);
        $studyField -> setTheme($f['theme']);     
        $manager->persist($studyField);
        $filieres[] = $studyField;
      }

      // 4. On crée l'utilisateur
      $users = [];
      for ($i = 0; $i < 8; $i++){
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
        $user->setStudyField($faker->randomElement($filieres));
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setIsVerified(true);
        $user->setTwoFactorSecret('none');
        $manager->persist($user);
        $users[] = $user;
      }
      

      // 5. On crée les cartes
      $cardStates = [CardState::DRAFT, CardState::PUBLISHED, CardState::ARCHIVED];

      for ($i=0; $i < 20; $i++){
        $card = new Card();
        $card->setTitle($faker->sentence(6, true));
        $card->setDescription($faker->paragraphs(3, true));
        $card->setState($faker->randomElement($cardStates));
        $card->setCreatedAt(new \DateTimeImmutable());
        $card->setUser($faker->randomElement($users));
        $card->setCategory($faker->randomElement($sousCategories));

        $image = new Image();
        $seed = $faker->unique()->numberBetween(1, 10000);
        $imageUrl = "https://picsum.photos/seed/{$seed}/800/600";

        $image->setFileName($imageUrl);
        $image->setSize(0); 
            
        $card->addImage($image);
        $manager->persist($image);

        $manager->persist($card);
      }
      
      // 6. On valide tout
      $manager->flush();
    }
}