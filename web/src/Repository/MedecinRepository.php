<?php

namespace App\Repository;

use App\Entity\Medecin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Medecin>
 */
class MedecinRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Medecin::class);
    }

    // Example: Find doctors by specialty
    // public function findBySpecialite($specialiteId): array
    // {
    //    return $this->createQueryBuilder('m')
    //        ->andWhere('m.specialite = :val')
    //        ->setParameter('val', $specialiteId)
    //        ->getQuery()
    //        ->getResult();
    // }
}
