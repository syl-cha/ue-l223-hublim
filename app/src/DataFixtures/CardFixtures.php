<?php

namespace App\DataFixtures;

use App\Entity\Card;
use App\Entity\Image;
use App\Entity\Category;
use App\Entity\User;
use App\Enum\CardState;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class CardFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $cardStates = [CardState::DRAFT, CardState::PUBLISHED, CardState::ARCHIVED];

        // Récupérer toutes les sous-catégories
        $subcategories = [];
        $index = 0;
        while (true) {
            try {
                $subcategories[] = $this->getReference('subcategory_' . $index, Category::class);
                $index++;
            } catch (\OutOfBoundsException $e) {
                break;
            }
        }

        //On crée nos cartes
        for ($i=0; $i < 20; $i++){
            $card = new Card();
            $card->setTitle($faker->sentence(6, true));
            $card->setDescription($faker->paragraphs(3, true));
            $card->setState($faker->randomElement($cardStates));
            $card->setCreatedAt(new \DateTimeImmutable());

            //On choisit un user aléatoire 
            $users = [];
            for ($j = 0; $j<10; $j++) {
                $users[] = $this->getReference('user_' . $j, User::class);
            }
            $card->setUser($faker->randomElement($users));

            // Sous-catégorie aléatoire
            $card->setCategory($faker->randomElement($subcategories));

            $image = new Image();
            $seed = $faker->unique()->numberBetween(1, 10000);
            $imageUrl = "https://picsum.photos/seed/{$seed}/800/600";

            $image->setFileName($imageUrl);
            $image->setSize(0); 
                
            $card->addImage($image);
            $manager->persist($image);

            $manager->persist($card);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            CategoryFixtures::class,
        ];
    }
}