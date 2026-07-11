<?php

namespace App\Repository;

use App\Entity\Business;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use App\Dto\BusinessSearchFilter;

/**
 * @extends ServiceEntityRepository<Business>
 */
class BusinessRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Business::class);
    }

    public function findByFilter(BusinessSearchFilter $filter): array
    {
        $qb = $this->createQueryBuilder('b')
            ->select('b')
            ->leftJoin('b.business_type', 'bt')
            ->addSelect('bt');

        if ($filter->name) {
            $qb->andWhere("b.name LIKE :name")
               ->setParameter('name', '%'.$filter->name.'%');
        }

        if ($filter->city) {
            $qb->andWhere("b.city LIKE :city")
               ->setParameter('city', '%'.$filter->city.'%');
        }

        if ($filter->businessType) {
            $qb->andWhere("b.business_type = :businessType")
               ->setParameter('businessType', $filter->businessType);
        }

        return $qb->getQuery()->getResult();
    }

    public function findUniqueCities(): array
    {
        $results = $this->createQueryBuilder('b')
            ->select('DISTINCT b.city')
            ->orderBy('b.city', 'ASC')
            ->getQuery()
            ->getResult();

        return array_column($results, 'city');
    }
}
