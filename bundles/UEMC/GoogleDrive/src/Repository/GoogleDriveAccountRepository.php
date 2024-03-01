<?php

namespace UEMC\GoogleDrive\Repository;

use UEMC\GoogleDrive\Entity\GoogleDriveAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GoogleDriveAccount>
 *
 * @method GoogleDriveAccount|null find($id, $lockMode = null, $lockVersion = null)
 * @method GoogleDriveAccount|null findOneBy(array $criteria, array $orderBy = null)
 * @method GoogleDriveAccount[]    findAll()
 * @method GoogleDriveAccount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GoogleDriveAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GoogleDriveAccount::class);
    }

//    /**
//     * @return GoogleDriveAccount[] Returns an array of GoogleDriveAccount objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('g')
//            ->andWhere('g.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('g.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?GoogleDriveAccount
//    {
//        return $this->createQueryBuilder('g')
//            ->andWhere('g.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
