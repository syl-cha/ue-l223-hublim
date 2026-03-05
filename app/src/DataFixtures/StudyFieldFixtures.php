<?php

namespace App\DataFixtures;

use App\Entity\StudyField;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class StudyFieldFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
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

        // Pour pouvoir les réutiliser
        $this->addReference('studyfield_' . $index, $studyField);
        }

        $manager->flush();
    }
}