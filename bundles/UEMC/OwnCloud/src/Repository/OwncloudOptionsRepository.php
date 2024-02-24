<?php

namespace UEMC\OwnCloud\Repository;

use UEMC\OwnCloud\Entity\OwncloudOptions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OwncloudOptions>
 *
 * @method OwncloudOptions|null find($id, $lockMode = null, $lockVersion = null)
 * @method OwncloudOptions|null findOneBy(array $criteria, array $orderBy = null)
 * @method OwncloudOptions[]    findAll()
 * @method OwncloudOptions[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OwncloudOptionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OwncloudOptions::class);
    }

//    /**
//     * @return OwncloudOptions[] Returns an array of OwncloudOptions objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('o.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?OwncloudOptions
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
