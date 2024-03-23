<?php

namespace UEMC\Core\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Exception;

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
    public function newAcount(Account $account): void
    {
        try {
            $em=$this->getEntityManager();
            $em->persist($account);
            $em->flush();
        }catch (Exception | NonUniqueResultException $e)
        {
            throw new CloudException(ErrorTypes::ERROR_LOG_ACCOUNT->getErrorMessage().' - '.$e->getMessage(),
                                    ErrorTypes::ERROR_LOG_ACCOUNT->getErrorCode());
        }
    }

    /**
     * @return void
     * @throws CloudException
     */
    public function updateAcount(): void
    {
        try {
            $em=$this->getEntityManager();
            $em->flush();
        }catch (Exception $e)
        {
            throw new CloudException(ErrorTypes::ERROR_LOG_ACCOUNT->getErrorMessage().' - '.$e->getMessage(),
                ErrorTypes::ERROR_LOG_ACCOUNT->getErrorCode());
        }
    }

    /**
     * @param Account $account
     * @return Account|null
     * @throws NonUniqueResultException
     */
    public function getAccount(Account $account): Account|null
    {
        $qb = $this->createQueryBuilder('a');

        $qb->where('a.openid = :openid')
            ->setParameter('openid', $account->getOpenid());

        if (!$account->getOpenid()) {
            $qb->orWhere('a.URL = :url')
                ->andWhere('a.user = :user')
                ->setParameter('url', $account->getURL())
                ->setParameter('user', $account->getUser());
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
