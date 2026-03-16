<?php

namespace App\Repository;

use App\Entity\StudyField;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StudyField>
 */
class StudyFieldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudyField::class);
    }

    public function findAllGrouped(): array
    {
        $fields = $this->createQueryBuilder('s')
            ->orderBy('s.type', 'ASC')
            ->addOrderBy('s.theme', 'ASC')
            ->addOrderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();

        $grouped = [];
        foreach ($fields as $field) {
            $grouped[$field->getType()][$field->getTheme()][] = $field;
        }

        return $grouped;
    }
}
