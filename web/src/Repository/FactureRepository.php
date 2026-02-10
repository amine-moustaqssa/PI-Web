<?php

namespace App\Repository;

use App\Entity\Facture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Facture>
 */
class FactureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Facture::class);
    }
    public function searchFactures(string $searchTerm = '', string $status = ''): array
    {
        $qb = $this->createQueryBuilder('f');

        if (!empty($searchTerm)) {
            $qb->andWhere('f.reference LIKE :search OR f.montantTotal LIKE :search')
               ->setParameter('search', '%' . $searchTerm . '%');
        }

        if (!empty($status)) {
            $qb->andWhere('f.statut = :status')
               ->setParameter('status', $status);
        }

        return $qb->orderBy('f.id', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    //    /**
    //     * @return Facture[] Returns an array of Facture objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Facture
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
