<?php

namespace MetaCloudify\Core\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Exception;

use MetaCloudify\Core\Entity\Account;
use MetaCloudify\Core\Resources\ErrorTypes;
use MetaCloudify\Core\Service\CloudException;

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
     * @return Account|null
     * @throws CloudException
     */
    public function login(Account $account): ?Account
    {
        try {
            $accountExists=$this->getAccount($account);
            if($accountExists==null) //Se crea en BD
            {
                $this->newAcount($account);
                $accountExists=$this->getAccount($account); //Una vez guardada la nuva cuenta se recupera
            } else //Se recupera de BD
            {
                $accountExists->setLastSession($account->getLastSession());
                $accountExists->setLastIp($account->getLastIp());
                $this->updateAcount();
            }
            return $accountExists;
        }catch (CloudException | NonUniqueResultException $e) {
            throw new CloudException(ErrorTypes::ERROR_LOG_ACCOUNT->getErrorMessage().' - '.$e->getMessage(),
                ErrorTypes::ERROR_LOG_ACCOUNT->getErrorCode());
        }
    }

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
        }catch (Exception $e)
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
