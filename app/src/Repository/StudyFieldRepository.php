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
            ->addOrderBy('s.department', 'ASC')
            ->addOrderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();

        $grouped = [];
        foreach ($fields as $field) {
            $department = $field->getDepartment();
            $deptKey = $department ? $department->getCode() : 'none';

            // On initialise la structure pour le type et le département
            if (!isset($grouped[$field->getType()][$deptKey])) {
                $grouped[$field->getType()][$deptKey] = [
                    'department' => $department, // l'objet complet
                    'fields' => []
                ];
            }

            $grouped[$field->getType()][$deptKey]['fields'][] = $field;
        }

        return $grouped;
    }
}
