<?php

namespace App\DataFixtures;

use App\DataFixtures\Utils\FixtureStatusHelperTrait;
use App\Entity\Status;
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
    use FixtureStatusHelperTrait;

    private const SIZES = [
        'thumb'  => [400, 300],
        'medium' => [800, 600],
        'full'   => [1920, 1080],
    ];

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

        for ($i = 0; $i < 20; $i++) {
            $card = new Card();
            $card->setTitle($faker->sentence($faker->numberBetween(3, 12), true));
            $card->setDescription($faker->paragraphs($faker->numberBetween(1, 5), true));
            $card->setState($faker->randomElement($cardStates));
            $card->setCreatedAt(new \DateTimeImmutable());
            $card->setViews($faker->numberBetween(0, 50));

            $users = [];
            for ($j = 0; $j < 10; $j++) {
                $users[] = $this->getReference('user_' . $j, User::class);
            }
            $card->setUser($faker->randomElement($users));
            $card->setCategory($faker->randomElement($subcategories));

            // ~60% des cartes ont des images, ~40% n'en ont pas
            $hasImages = $faker->boolean(60);

            if ($hasImages) {
                $nbImages = $faker->numberBetween(1, 4);
                for ($k = 0; $k < $nbImages; $k++) {
                    $baseName = $this->downloadImage($faker->numberBetween(1, 500), $i . '-' . $k);

                    $image = new Image();
                    $image->setFileName($baseName);
                    $image->setSize(filesize($this->uploadDir . '/' . $baseName . '-full.webp'));
                    $image->setPosition($k);
                    $image->setAlt($card->getTitle());
                    $card->addImage($image);
                    $manager->persist($image);
                }
            }

            // Mettre une restriction sur le statut aléatoirement
            $status = $this->getAllStatuses($this->referenceRepository);
            // Restriction dans seulement 40% des cas.
            $hasStatusRestriction = $faker->boolean(40);
            if ($hasStatusRestriction) {
                $card->addTargetStatus($faker->randomElement($status));
            }


            $manager->persist($card);
        }

        $manager->flush();
    }

    private function downloadImage(int $seed, string $suffix): string
    {
        $baseName = 'fixture-' . $seed . '-' . $suffix;

        foreach (self::SIZES as $sizeSuffix => [$width, $height]) {
            $filename = $baseName . '-' . $sizeSuffix . '.webp';
            $dest = $this->uploadDir . '/' . $filename;

            if (!file_exists($dest)) {
                $content = @file_get_contents("https://picsum.photos/seed/{$seed}/{$width}/{$height}");
                if ($content !== false) {
                    file_put_contents($dest, $content);
                } else {
                    // Placeholder coloré si pas de connexion
                    $img = imagecreatetruecolor($width, $height);
                    $color = imagecolorallocate($img, rand(100, 200), rand(100, 200), rand(100, 200));
                    imagefill($img, 0, 0, $color);
                    imagewebp($img, $dest, 80);
                    imagedestroy($img);
                }
            }
        }

        return $baseName;
    }

    public function getDependencies(): array
    {
        return [CategoryFixtures::class, UserFixtures::class];
    }
}
