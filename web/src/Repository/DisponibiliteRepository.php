<?php

namespace App\Repository;

use App\Entity\Disponibilite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Disponibilite>
 */
class DisponibiliteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Disponibilite::class);
    }

    /**
     * Find disponibilites that overlap with the given time slot for a medecin.
     * Optionally exclude a specific disponibilite (useful for edits).
     */
    public function findOverlapping(
        $medecin,
        int $jourSemaine,
        \DateTimeInterface $heureDebut,
        \DateTimeInterface $heureFin,
        ?int $excludeId = null
    ): array {
        $qb = $this->createQueryBuilder('d')
            ->where('d.medecin = :medecin')
            ->andWhere('d.jourSemaine = :jour')
            ->andWhere('d.heureDebut < :fin')
            ->andWhere('d.heureFin > :debut')
            ->setParameter('medecin', $medecin)
            ->setParameter('jour', $jourSemaine)
            ->setParameter('debut', $heureDebut)
            ->setParameter('fin', $heureFin);

        if ($excludeId !== null) {
            $qb->andWhere('d.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getResult();
    }
}
