<?php

namespace App\DataFixtures;

use App\Entity\StudyField;
use App\Entity\Department;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class StudyFieldFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        //On charge les filières depuis le json
        $filiereData = json_decode(file_get_contents(__DIR__ . '/data/filieres.json'), true);

        $filieres = [];
        $index = 0;

        foreach ($filiereData as $f) {
            $studyField = new StudyField();
            $studyField->setName($f['nom']);
            $studyField->setType($f['type']);

            // On récupère l'entité Department via la référence créée dans DepartmentFixtures
            $department = $this->getReference('department_' . $f['department'], Department::class);
            $studyField->setDepartment($department);

            $manager->persist($studyField);
            $filieres[] = $studyField;

            // Pour pouvoir les réutiliser
            $this->addReference('studyfield_' . $index, $studyField);
            $index++;
        }

        $manager->flush();
    }
    // pour indiquer que la table Departement doit être construire AVANT StudyField
    public function getDependencies(): array
    {
        return [
            DepartmentFixtures::class,
        ];
    }
}
