<?php

namespace App\DataFixtures;

use App\Entity\Department;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class DepartmentFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $departmentData = json_decode(file_get_contents(__DIR__ . '/data/departements.json'), true);

        foreach ($departmentData as $d) {
            $department = new Department();
            $department->setCode($d['code']);
            $department->setLabel($d['label']);
            $department->setColor($d['color']);

            $manager->persist($department);

            // Pour pouvoir les réutiliser dans StudyFieldFixtures
            $this->addReference('department_' . $d['code'], $department);
        }

        $manager->flush();
    }
}
