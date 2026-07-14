<?php

namespace App\Repository;

use App\Entity\Business;
use App\Entity\SaleRecord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SaleRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SaleRecord::class);
    }

    public function getMonthlyRevenue(Business $business): array
    {
        $records = $this->createQueryBuilder('s')
            ->where('s.business = :business')
            ->andWhere("s.status = 'fulfilled'")
            ->setParameter('business', $business)
            ->orderBy('s.orderedAt', 'ASC')
            ->getQuery()
            ->getResult();

        $grouped = [];
        foreach ($records as $record) {
            $month = $record->getOrderedAt()->format('Y-m');
            if (!isset($grouped[$month])) {
                $grouped[$month] = 0.0;
            }
            $grouped[$month] += $record->getPackagePrice();
        }

        $result = [];
        foreach ($grouped as $month => $total) {
            $result[] = [
                'month' => $month,
                'total' => $total
            ];
        }

        return $result;
    }

    public function getCategoryStats(Business $business): array
    {
        return $this->createQueryBuilder('s')
            ->select('s.categoryName, COUNT(s.id) as total, SUM(s.packagePrice) as revenue')
            ->where('s.business = :business')
            ->andWhere("s.status = 'fulfilled'")
            ->setParameter('business', $business)
            ->groupBy('s.categoryName')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getRecordsByPeriod(Business $business, string $period): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.business = :business')
            ->setParameter('business', $business)
            ->orderBy('s.orderedAt', 'ASC');

        $since = null;
        if ($period === 'today') {
            $since = new \DateTimeImmutable('today midnight');
        } elseif ($period === 'last_week') {
            $since = new \DateTimeImmutable('-7 days midnight');
        } elseif ($period === 'last_month') {
            $since = new \DateTimeImmutable('-30 days midnight');
        }

        if ($since) {
            $qb->andWhere('s.fulfilledAt >= :since')
               ->setParameter('since', $since);
        }

        return $qb->getQuery()->getResult();
    }
}
