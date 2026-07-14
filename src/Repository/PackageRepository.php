<?php

namespace App\Repository;

use App\Dto\PackageSearchFilter;
use App\Entity\Package;
use App\Entity\Business;
use App\Entity\Consumer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Package>
 */
class PackageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Package::class);
    }

    public function findByFilter(PackageSearchFilter $filter, ?Business $business = null, bool $excludeOrdered = false, ?Consumer $consumer = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->leftJoin('p.business', 'b')
            ->addSelect('b');

        if ($excludeOrdered) {
            $expiresAt = new \DateTimeImmutable('-24 hours');
            $qb->leftJoin('p.consumer_order', 'co')
                ->andWhere('co.id IS NULL')
                ->andWhere('p.created_at >= :expiresAt')
                ->setParameter('expiresAt', $expiresAt);
        }
        if ($business) {
            $qb->andWhere("p.business = :business")
               ->setParameter('business', $business);
        } elseif ($filter->business) {
            $qb->andWhere("p.business = :filterBusiness")
               ->setParameter('filterBusiness', $filter->business);
        }

        if ($filter->city) {
            $qb->andWhere("b.city LIKE :city")
               ->setParameter('city', '%'.$filter->city.'%');
        }

        if($filter->name){
            $qb->andWhere("p.name LIKE :searchWord OR p.description LIKE :searchWord")
                ->setParameter('searchWord', '%'.$filter->name.'%');
        }
        if($filter->minPrice){
            $qb->andWhere("p.price >= :minPrice")
                ->setParameter('minPrice', $filter->minPrice);
        }
        if($filter->maxPrice){
            $qb->andWhere("p.price <= :maxPrice")
                ->setParameter('maxPrice', $filter->maxPrice);
        }
        if($filter->category){
            $qb->andWhere("p.category = :category")
                ->setParameter('category', $filter->category);
        }

        if ($consumer !== null && !$consumer->getPreferredCategories()->isEmpty()) {
            $qb->andWhere('p.category IN (:preferredCategories)')
               ->setParameter('preferredCategories', $consumer->getPreferredCategories()->toArray());
        }

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return Package[] Returns an array of Package objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Package
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    public function findByFavoriteBusinesses(Consumer $consumer, PackageSearchFilter $filter): array
    {
        $businesses = $consumer->getFavoriteBusinesses();

        if ($businesses->isEmpty()) {
            return [];
        }

        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.consumer_order', 'co')
            ->leftJoin('p.business', 'b')
            ->addSelect('b')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->where('p.business IN (:businesses)')
            ->andWhere('co.id IS NULL')
            ->andWhere('p.created_at >= :expiresAt')
            ->setParameter('businesses', $businesses->toArray())
            ->setParameter('expiresAt', new \DateTimeImmutable('-24 hours'));

        if (!$consumer->getPreferredCategories()->isEmpty()) {
            $qb->andWhere('p.category IN (:preferredCategories)')
               ->setParameter('preferredCategories', $consumer->getPreferredCategories()->toArray());
        }

        if ($filter->city) {
            $qb->andWhere("b.city LIKE :city")
               ->setParameter('city', '%'.$filter->city.'%');
        }

        if ($filter->name) {
            $qb->andWhere("p.name LIKE :searchWord OR p.description LIKE :searchWord")
               ->setParameter('searchWord', '%'.$filter->name.'%');
        }

        if ($filter->minPrice) {
            $qb->andWhere("p.price >= :minPrice")
               ->setParameter('minPrice', $filter->minPrice);
        }

        if ($filter->maxPrice) {
            $qb->andWhere("p.price <= :maxPrice")
               ->setParameter('maxPrice', $filter->maxPrice);
        }

        if ($filter->category) {
            $qb->andWhere("p.category = :category")
               ->setParameter('category', $filter->category);
        }

        return $qb->getQuery()->getResult();
    }
}
