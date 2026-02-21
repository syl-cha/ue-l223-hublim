<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
  public function load(ObjectManager $manager): void
  {
    // $product = new Product();
    // $manager->persist($product);

    // Traduction des fichiers JSON pour les catégories et filières
    // 1. on récupère le JSON
    // 2. on le transforme en tableau associatif
    // 3. on boucle et on créée les entités correspondantes.

    // CATÉGORIES
    $jsonCategories = file_get_contents(__DIR__ . '/data/categories.json');
    $categoriesData = json_decode($jsonCategories, true);
    foreach ($categoriesData as $categorie) {
      // TODO
    }

    // FILIÈRES
    $jsonFilieres = file_get_contents(__DIR__ . '/data/filieres.json');
    $filieresData = json_decode($jsonFilieres, true);
    foreach ($filieresData as $filiere) {
      // TODO
    }


    $manager->flush();
  }
}
