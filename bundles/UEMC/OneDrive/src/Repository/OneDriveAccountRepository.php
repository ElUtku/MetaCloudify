<?php

namespace UEMC\OneDrive\Repository;

use UEMC\OneDrive\Entity\OneDriveAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OneDriveAccount>
 *
 * @method OneDriveAccount|null find($id, $lockMode = null, $lockVersion = null)
 * @method OneDriveAccount|null findOneBy(array $criteria, array $orderBy = null)
 * @method OneDriveAccount[]    findAll()
 * @method OneDriveAccount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OneDriveAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OneDriveAccount::class);
    }

//    /**
//     * @return OneDriveAccount[] Returns an array of OneDriveAccount objects
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

//    public function findOneBySomeField($value): ?OneDriveAccount
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
