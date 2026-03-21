<?php

namespace App\DataFixtures\Utils;

use App\Entity\Status;
use Doctrine\Common\DataFixtures\ReferenceRepository;

trait FixtureStatusHelperTrait
{
    /**
     * Récupère la liste de tous les statuts disponibles.
     *
     * @return Status[]
     */
    protected function getAllStatuses(ReferenceRepository $referenceRepository): array
    {
        return [
            $referenceRepository->getReference('status_student', Status::class),
            $referenceRepository->getReference('status_teacher', Status::class),
            $referenceRepository->getReference('status_staff', Status::class),
        ];
    }
}
