<?php

namespace App\Repository;

use App\Entity\ConstanteVitale;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConstanteVitale>
 */
class ConstanteVitaleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConstanteVitale::class);
    }

    /**
     * Récupère l'historique des constantes vitales avec filtres multicritères.
     * Permet la comparaison entre consultations avec graphiques de tendance.
     *
     * @param string|null $type      Filtrer par type de constante
     * @param \DateTimeInterface|null $dateFrom  Date de début
     * @param \DateTimeInterface|null $dateTo    Date de fin
     * @param array|null $consultationIds  Filtrer par un ou plusieurs IDs de consultation
     * @return ConstanteVitale[]
     */
    public function findHistorique(?string $type = null, ?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null, ?array $consultationIds = null): array
    {
        $qb = $this->createQueryBuilder('cv')
            ->join('cv.consultation_id', 'c')
            ->addSelect('c')
            ->orderBy('cv.date_prise', 'ASC');

        if ($type !== null && $type !== '') {
            $qb->andWhere('cv.type = :type')
               ->setParameter('type', $type);
        }

        if ($dateFrom !== null) {
            $qb->andWhere('cv.date_prise >= :dateFrom')
               ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo !== null) {
            $qb->andWhere('cv.date_prise <= :dateTo')
               ->setParameter('dateTo', $dateTo);
        }

        if ($consultationIds !== null && count($consultationIds) > 0) {
            $qb->andWhere('c.id IN (:consultationIds)')
               ->setParameter('consultationIds', $consultationIds);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère tous les types distincts de constantes vitales enregistrés.
     *
     * @return string[]
     */
    public function findDistinctTypes(): array
    {
        return array_column(
            $this->createQueryBuilder('cv')
                ->select('DISTINCT cv.type')
                ->orderBy('cv.type', 'ASC')
                ->getQuery()
                ->getScalarResult(),
            'type'
        );
    }

    /**
     * Récupère les constantes groupées par consultation pour comparaison.
     *
     * @param string $type Le type de constante à comparer
     * @return array Données groupées par consultation
     */
    public function findForComparison(string $type): array
    {
        return $this->createQueryBuilder('cv')
            ->join('cv.consultation_id', 'c')
            ->addSelect('c')
            ->andWhere('cv.type = :type')
            ->setParameter('type', $type)
            ->orderBy('c.dateEffectuee', 'ASC')
            ->addOrderBy('cv.date_prise', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count constantes vitales recorded today.
     */
    public function countToday(): int
    {
        $start = new \DateTime('today 00:00:00');
        $end = new \DateTime('today 23:59:59');

        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.date_prise BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    //    /**
    //     * @return ConstanteVitale[] Returns an array of ConstanteVitale objects
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

    //    public function findOneBySomeField($value): ?ConstanteVitale
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
