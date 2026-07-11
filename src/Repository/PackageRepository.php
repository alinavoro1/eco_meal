<?php

namespace App\Repository;

use App\Dto\PackageSearchFilter;
use App\Entity\Package;
use App\Entity\Business;
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

    public function findByFilter(PackageSearchFilter $filter, ?Business $business = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->leftJoin('p.business', 'b')
            ->addSelect('b');

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
}
