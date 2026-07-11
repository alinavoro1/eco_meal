<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use App\Dto\OrderSearchFilter;
use App\Entity\Consumer;
use App\Entity\Business;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function findByFilter(OrderSearchFilter $filter, ?Consumer $consumerContext = null, ?Business $businessContext = null): array
    {
        $qb = $this->createQueryBuilder('o')
            ->select('o')
            ->join('o.package', 'p')
            ->addSelect('p')
            ->join('o.consumer', 'c')
            ->addSelect('c')
            ->join('p.business', 'b')
            ->addSelect('b');

        if ($consumerContext) {
            $qb->andWhere("o.consumer = :consumerContext")
               ->setParameter('consumerContext', $consumerContext);
        } elseif ($filter->consumer) {
            $qb->andWhere("o.consumer = :filterConsumer")
               ->setParameter('filterConsumer', $filter->consumer);
        }

        if ($businessContext) {
            $qb->andWhere("p.business = :businessContext")
               ->setParameter('businessContext', $businessContext);
        } elseif ($filter->business) {
            $qb->andWhere("p.business = :filterBusiness")
               ->setParameter('filterBusiness', $filter->business);
        }

        if ($filter->packageName) {
            $qb->andWhere("p.name LIKE :packageName")
               ->setParameter('packageName', '%'.$filter->packageName.'%');
        }

        return $qb->getQuery()->getResult();
    }
}
