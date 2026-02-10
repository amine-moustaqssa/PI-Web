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
            ->andWhere('c.dateEffectuee BETWEEN :start AND :end')
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
            ->orderBy('c.dateEffectuee', 'DESC')
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
            ->orderBy('c.dateEffectuee', 'DESC')
            ->getQuery()
            ->getResult();
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
            $qb->andWhere('c.dateEffectuee = :date')
                ->setParameter('date', $date->format('Y-m-d'));
        }

        if ($statut !== null && $statut !== '') {
            $qb->andWhere('c.statut = :statut')
                ->setParameter('statut', $statut);
        }

        return $qb->getQuery()->getResult();
    }
}
