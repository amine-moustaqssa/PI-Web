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

    public function findByFilters($jour, $recurrent, $medecin = null)
    {
        $qb = $this->createQueryBuilder('d');

        // Filtre de sécurité : Si un médecin est passé, on limite les résultats à lui
        if ($medecin) {
            $qb->andWhere('d.medecin = :medecin')
            ->setParameter('medecin', $medecin);
        }

        // Filtre par Jour
        if ($jour) {
            $qb->andWhere('d.jourSemaine = :jour')
            ->setParameter('jour', $jour);
        }

        // Filtre par Récurrence
        if ($recurrent !== null && $recurrent !== '') {
            $qb->andWhere('d.estRecurrent = :recurrent')
            ->setParameter('recurrent', $recurrent);
        }

        return $qb->getQuery()->getResult();
    }
}
