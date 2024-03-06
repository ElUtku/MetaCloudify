<?php

namespace UEMC\Core\Repository;

use DateTime;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use UEMC\Core\Entity\Account;
use UEMC\Core\Resources\ErrorTypes;
use UEMC\Core\Service\CloudException;

/**
 * @extends EntityRepository<Account>
 *
 * @method Account|null find($id, $lockMode = null, $lockVersion = null)
 * @method Account|null findOneBy(array $criteria, array $orderBy = null)
 * @method Account[]    findAll()
 * @method Account[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AccountRepository extends EntityRepository
{

    /**
     * @param Account $account
     * @return void
     * @throws CloudException
     */
    public function addAcount(Account $account): void
    {
        try {
            $em=$this->getEntityManager();
            $accountStored=$this->existAccount($account);
            if($accountStored==null)
            {
                $em->persist($account);
            }else{
                $accountStored->setLastIp($account->getLastIp());
                $accountStored->setLastSession($account->getLastSession());
            }
            $em->flush();
        }catch (NonUniqueResultException $e)
        {
            throw new CloudException(ErrorTypes::ERROR_ADD_ACCOUNT->getErrorMessage().' - '.$e->getMessage(),
                                    ErrorTypes::ERROR_ADD_ACCOUNT->getErrorCode());
        }

    }


    /**
     * @param Account $account
     * @return Account|null
     * @throws NonUniqueResultException
     */
    private function existAccount(Account $account): Account|null
    {
        return $this->createQueryBuilder('a')
              ->andWhere('a.openid = :val')
              ->setParameter('val', $account->getOpenid())
              ->getQuery()
              ->getOneOrNullResult();
    }
//    /**
//     * @return Account[] Returns an array of Account objects
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

//    public function findOneBySomeField($value): ?Account
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

}
