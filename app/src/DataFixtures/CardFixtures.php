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
use Symfony\Component\Filesystem\Filesystem;

class CardFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(private string $uploadDir) {}

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $cardStates = [CardState::DRAFT, CardState::PUBLISHED, CardState::ARCHIVED];
        $fs = new Filesystem();
        $fs->mkdir($this->uploadDir);

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
        for ($i = 0; $i < 20; $i++) {
            $card = new Card();
            $card->setTitle($faker->sentence(6, true));
            $card->setDescription($faker->paragraphs(3, true));
            $card->setState($faker->randomElement($cardStates));
            $card->setCreatedAt(new \DateTimeImmutable());
            $card->setViews($faker->numberBetween(0, 50));

            //On choisit un user aléatoire
            $users = [];
            for ($j = 0; $j < 10; $j++) {
                $users[] = $this->getReference('user_' . $j, User::class);
            }
            $card->setUser($faker->randomElement($users));
            $card->setCategory($faker->randomElement($subcategories));

            // Entre 1 et 4 images par annonce
            $nbImages = $faker->numberBetween(1, 4);
            for ($k = 0; $k < $nbImages; $k++) {
                $filename = $this->downloadImage($faker->numberBetween(1, 500), $i . '-' . $k);

                $image = new Image();
                $image->setFileName($filename);
                $image->setSize(filesize($this->uploadDir . '/' . $filename));
                $image->setPosition($k);
                $image->setAlt($card->getTitle());
                $card->addImage($image);
                $manager->persist($image);
            }

            $manager->persist($card);
        }

        $manager->flush();
    }

    private function downloadImage(int $seed, string $suffix): string
    {
        $filename = 'fixture-' . $seed . '-' . $suffix . '.webp';
        $dest = $this->uploadDir . '/' . $filename;

        if (!file_exists($dest)) {
            $content = @file_get_contents("https://picsum.photos/seed/{$seed}/800/600");
            if ($content !== false) {
                file_put_contents($dest, $content);
            } else {
                // Image placeholder colorée si pas de connexion
                $img = imagecreatetruecolor(800, 600);
                $color = imagecolorallocate($img, rand(100, 200), rand(100, 200), rand(100, 200));
                imagefill($img, 0, 0, $color);
                imagewebp($img, $dest, 80);
                imagedestroy($img);
            }
        }

        return $filename;
    }

    public function getDependencies(): array
    {
        return [CategoryFixtures::class, UserFixtures::class];
    }
}
