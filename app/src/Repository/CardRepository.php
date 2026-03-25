<?php

namespace App\Repository;

use App\Entity\Card;
use Doctrine\ORM\Query;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Card>
 */
class CardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Card::class);
    }

    /** 
     * Fonction de recherche parmis les cartes (titres, contenu, catégorie)
     * 
     * @param string $recherche mot/chaine de caractère à rechercher
     * @return array tableau des cartes contenant la recherche
     */

    public function searchFunction(string $recherche):array {
        return 
            $this->createQueryBuilder('card') //createQueryBuilder crée une requête SQL à l'aide de Doctrine
            ->leftJoin('card.category', 'c') //Jointure sur category
            ->where('card.title LIKE :recherche')
            ->orWhere('card.description LIKE :recherche')
            ->setParameter('recherche', '%' . $recherche . '%')
            ->getQuery() // transforme la requête en objet Query, prêt à être éxecuté
            ->getResult(); //envoie la requête
    }

    
    public function findByCategories(array $categories): Query
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.category IN (:categories)')
            ->setParameter('categories', $categories)
            ->orderBy('c.id', 'DESC')
            ->getQuery();
    }


//    /**
//     * @return Card[] Returns an array of Card objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Card
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
