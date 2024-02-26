<?php

namespace App\Repository;

use App\Entity\Acount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Acount>
 *
 * @method Acount|null find($id, $lockMode = null, $lockVersion = null)
 * @method Acount|null findOneBy(array $criteria, array $orderBy = null)
 * @method Acount[]    findAll()
 * @method Acount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AcountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Acount::class);
    }

//    /**
//     * @return Acount[] Returns an array of Acount objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Acount
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
