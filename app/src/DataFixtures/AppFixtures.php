<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Card;
use App\Enum\CardState;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
  private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        // 1. Création d'un Utilisateur de test
        $user = new User();
        $user->setEmail('etudiant@etu.unilim.fr');
        $user->setFirstName('Lucie');
        $user->setLastName('Lieuve');
        $user->setIsVerified(true);
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setTwoFactorSecret('secret123'); // l'entité le demande en non null
        
        // Hashage du mot de passe
        $password = $this->hasher->hashPassword($user, 'test1234');
        $user->setPassword($password);

        $manager->persist($user);

        // 2. Création d'une Carte liée à cet utilisateur
        
        $card = new Card();
        $card->setTitle('Ma première carte de révision');
        $card->setDescription('Ceci est une description de test pour mon examen.');
        $card->setState(CardState::DRAFT);
        $card->setCreatedAt(new \DateTimeImmutable());

        $manager->persist($card);

        // 3. On envoie tout en base
        $manager->flush();
    }
}
