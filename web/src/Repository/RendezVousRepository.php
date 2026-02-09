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
     * Recherche les rendez-vous par mot clé (Nom médecin, spécialité ou patient)
     */
    public function findBySearchQuery(?string $query)
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.profil', 'p') // On joint le profil pour chercher aussi par nom de patient
            ->addSelect('p')
            ->orderBy('r.date_debut', 'DESC');

        if ($query) {
            $qb->andWhere('r.type LIKE :term OR p.nom LIKE :term OR p.prenom LIKE :term')
               ->setParameter('term', '%' . $query . '%');
        }

        return $qb->getQuery()->getResult();
    }
    //    /**
    //     * @return RendezVous[] Returns an array of RendezVous objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?RendezVous
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
