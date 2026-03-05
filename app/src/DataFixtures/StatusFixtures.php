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
        $status = new Status();
        $status->setLabel(StatusLabel::STUDENT);
        $manager->persist($status);

        //Pour pouvoir le réutiliser dans UserFixtures
        $this->addReference('status_student', $status);

        $manager->flush();
    }
}
