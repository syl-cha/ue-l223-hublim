<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        //On charge les catégories depuis le json
        $categoryData = json_decode(file_get_contents(__DIR__ . '/data/categories.json'), true);

        // 1. On crée les Catégorie (Besoin de : name, slug)
        $categories = [];
        $sousCategories = [];

        foreach ($categoryData as $c){
        $category = new Category();
        $category -> setName($c['nom']);
        $category -> setSlug($c['slug']);
        $category->setColor($c['color'] ?? null);
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

        // Stocke toutes les sous-catégories pour les cartes
        foreach ($sousCategories as $index => $sc) {
            $this->addReference('subcategory_' . $index, $sc);
        }

        $manager->flush();
    }
}