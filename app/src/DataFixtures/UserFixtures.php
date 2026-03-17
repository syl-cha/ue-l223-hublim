<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Status;
use App\Entity\StudyField;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    private $hasher;
    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Récupérer le statut et les filières
        $statusStudent = $this->getReference('status_student', Status::class);
        $statusTeacher = $this->getReference('status_teacher', Status::class);
        $statusStaff = $this->getReference('status_staff', Status::class);

        $studyFields = [];
        $index = 0;
        while (true) {
            try {
                $studyFields[] = $this->getReference('studyfield_' . $index, StudyField::class);
                $index++;
            } catch (\OutOfBoundsException $e) {
                break;
            }
        }

        // création d'un admin
        $admin = new User();
        $admin->setEmail('admin@test.com');
        $admin->setFirstName('Admin');
        $admin->setLastName('Système');
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin123'));
        $admin->setStudyField(null);
        $admin->setStatus($statusStaff);
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setCreatedAt(new \DateTimeImmutable());
        $admin->setIsVerified(true);
        $admin->setTwoFactorSecret('none');

        $manager->persist($admin);

        //On crée les utilisateurs
        for ($i = 0; $i < 10; $i++) {
            //On définit le nom d'abord pour pouvoir réutiliser les variables dans l'email
            $firstName = $faker->firstName();
            $lastName  = $faker->lastName();

            $user = new User();
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setEmail(strtolower($firstName . '.' . $lastName) . '@etu.unilim.fr');
            $user->setPassword($this->hasher->hashPassword($user, 'password'));
            $user->setStatus($statusStudent);
            $user->setStudyField($faker->randomElement($studyFields));

            $user->setCreatedAt(new \DateTimeImmutable());
            $user->setIsVerified(true);
            $user->setTwoFactorSecret('none');
            $user->setRoles(['ROLE_USER']);

            $manager->persist($user);

            // Stocker pour CardFixtures
            $this->addReference('user_' . $i, $user);

            $manager->persist($user);
        }

        $manager->flush();
    }

    //On définit l'ordre d'executiondes fixtures
    public function getDependencies(): array
    {
        return [
            StatusFixtures::class,
            StudyFieldFixtures::class,
        ];
    }
}
