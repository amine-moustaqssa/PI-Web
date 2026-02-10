<?php

namespace App\Repository;

use App\Entity\RendezVous;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RendezVous>
 */
class RendezVousRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RendezVous::class);
    }

    /**
     * Counts appointments for a specific medical profile for the current day.
     * Useful for dashboard statistics.
     */
    public function countTodayByProfil($profilMedical): int
    {
        $start = new \DateTime('today 00:00:00');
        $end = new \DateTime('today 23:59:59');

        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.profil = :profil')
            ->andWhere('r.date_debut BETWEEN :start AND :end')
            ->setParameter('profil', $profilMedical)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Search for appointments by keyword (Type, Patient Name, or Patient Surname)
     */
    public function findBySearchQuery(?string $query)
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.profil', 'p') // Join profile to search by patient details
            ->addSelect('p')
            ->orderBy('r.date_debut', 'DESC');

        if ($query) {
            $qb->andWhere('r.type LIKE :term OR p.nom LIKE :term OR p.prenom LIKE :term')
                ->setParameter('term', '%' . $query . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Fetches today's appointments for a specific medical profile, ordered by time.
     * Used for the planning table on the medecin dashboard.
     *
     * @return RendezVous[]
     */
    public function findTodayByProfil($profilMedical): array
    {
        $start = new \DateTime('today 00:00:00');
        $end = new \DateTime('today 23:59:59');

        return $this->createQueryBuilder('r')
            ->andWhere('r.profil = :profil')
            ->andWhere('r.date_debut BETWEEN :start AND :end')
            ->setParameter('profil', $profilMedical)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('r.date_debut', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
