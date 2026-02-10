<?php

namespace App\Repository;

use App\Entity\Consultation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Consultation>
 */
class ConsultationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Consultation::class);
    }
    // src/Repository/ConsultationRepository.php
    public function countTodayByMedecin($medecin): int
    {
        $start = new \DateTime('today 00:00:00');
        $end = new \DateTime('today 23:59:59');

        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.medecin = :medecin')
            ->andWhere('c.date_effectuee BETWEEN :start AND :end')
            ->setParameter('medecin', $medecin)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find all consultations for a specific doctor, ordered by date desc.
     *
     * @return Consultation[]
     */
    public function findByMedecin($medecin): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.medecin = :medecin')
            ->setParameter('medecin', $medecin)
            ->orderBy('c.date_effectuee', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find consultations for a specific doctor filtered by statut.
     *
     * @return Consultation[]
     */
    public function findByMedecinAndStatut($medecin, string $statut): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.medecin = :medecin')
            ->andWhere('c.statut = :statut')
            ->setParameter('medecin', $medecin)
            ->setParameter('statut', $statut)
            ->orderBy('c.date_effectuee', 'DESC')
            ->getQuery()
            ->getResult();
    }


    //    /**
    //     * @return Consultation[] Returns an array of Consultation objects
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

    //    public function findOneBySomeField($value): ?Consultation
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
