<?php

namespace App\DataFixtures;

use App\Entity\Status;
use App\Enum\StatusLabel;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class StatusFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Status Etudiant
        $statusStudent = new Status();
        $statusStudent->setLabel(StatusLabel::STUDENT);
        $manager->persist($statusStudent);
        //Pour pouvoir le réutiliser dans UserFixtures
        $this->addReference('status_student', $statusStudent);

        // Status Enseignant
        $statusTeacher = new Status();
        $statusTeacher->setLabel(StatusLabel::TEACHER);
        $manager->persist($statusTeacher);
        $this->addReference('status_teacher', $statusTeacher);

         // Status Personnel
        $statusStaff = new Status();
        $statusStaff->setLabel(StatusLabel::STAFF);
        $manager->persist($statusStaff);
        $this->addReference('status_staff', $statusStaff);

        $manager->flush();
    }
}
