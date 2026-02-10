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

    /**
     * Recherche multicritère des consultations selon medecin, date et statut
     *
     * @param int|null $medecinId
     * @param \DateTimeInterface|null $date
     * @param string|null $statut
     * @return Consultation[]
     */
    public function searchConsultations(?int $medecinId, ?\DateTimeInterface $date, ?string $statut): array
    {
        $qb = $this->createQueryBuilder('c');

        if ($medecinId !== null) {
            $qb->andWhere('c.medecin = :medecinId')
                ->setParameter('medecinId', $medecinId);
        }

        if ($date !== null) {
            $qb->andWhere('c.date_effectuee = :date')
                ->setParameter('date', $date->format('Y-m-d'));
        }

        if ($statut !== null && $statut !== '') {
            $qb->andWhere('c.statut = :statut')
                ->setParameter('statut', $statut);
        }

        return $qb->getQuery()->getResult();
    }

    // --- Méthodes générées par défaut, conservées ---
    // /**
    //  * @return Consultation[] Returns an array of Consultation objects
    //  */
    // public function findByExampleField($value): array
    // {
    //     return $this->createQueryBuilder('c')
    //         ->andWhere('c.exampleField = :val')
    //         ->setParameter('val', $value)
    //         ->orderBy('c.id', 'ASC')
    //         ->setMaxResults(10)
    //         ->getQuery()
    //         ->getResult()
    //     ;
    // }

    // public function findOneBySomeField($value): ?Consultation
    // {
    //     return $this->createQueryBuilder('c')
    //         ->andWhere('c.exampleField = :val')
    //         ->setParameter('val', $value)
    //         ->getQuery()
    //         ->getOneOrNullResult()
    //     ;
    // }
}