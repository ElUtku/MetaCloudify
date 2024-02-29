<?php

namespace UEMC\OwnCloud\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use UEMC\OwnCloud\Entity\OwnCloudAccount;

/**
 * @extends ServiceEntityRepository<OwnCloudAccount>
 *
 * @method OwnCloudAccount|null find($id, $lockMode = null, $lockVersion = null)
 * @method OwnCloudAccount|null findOneBy(array $criteria, array $orderBy = null)
 * @method OwnCloudAccount[]    findAll()
 * @method OwnCloudAccount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OwnCloudAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OwnCloudAccount::class);
    }

//    /**
//     * @return OwnCloudAccount[] Returns an array of OwnCloudAccount objects
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

//    public function findOneBySomeField($value): ?OwnCloudAccount
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
