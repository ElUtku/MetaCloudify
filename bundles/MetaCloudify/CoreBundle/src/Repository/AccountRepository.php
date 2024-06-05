<?php

namespace MetaCloudify\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Exception;

use GuzzleHttp\Exception\ConnectException;
use MetaCloudify\CoreBundle\Entity\Account;
use MetaCloudify\CoreBundle\Resources\ErrorTypes;
use MetaCloudify\CoreBundle\Service\CloudException;
use Symfony\Component\HttpFoundation\Response;

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
     *
     * Registra los datos de la cuenta en la BBDD (hora e ip). Si no existe se crea la entrada.
     *
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
        }catch (CloudException $e) {
            throw new CloudException(ErrorTypes::ERROR_LOG_ACCOUNT->getErrorMessage().' - '.$e->getMessage(),
                ErrorTypes::ERROR_LOG_ACCOUNT->getErrorCode());
        }
    }

    /**
     *
     * Da de alta una nueva cuenta en la BBDD.
     *
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
        }catch (ConnectException |Exception $e)
        {
            throw new CloudException(ErrorTypes::ERROR_LOG_ACCOUNT->getErrorMessage().' - '.$e->getMessage(),
                                    ErrorTypes::ERROR_LOG_ACCOUNT->getErrorCode());
        }
    }

    /**
     *
     * Guarda los cambios en la base de datos.
     *
     * @return void
     * @throws CloudException
     */
    public function updateAcount(): void
    {
        try {
            $em=$this->getEntityManager();
            $em->flush();
        }catch (ConnectException | Exception $e)
        {
            throw new CloudException(ErrorTypes::ERROR_LOG_ACCOUNT->getErrorMessage().' - '.$e->getMessage(),
                ErrorTypes::ERROR_LOG_ACCOUNT->getErrorCode());
        }
    }

    /**
     *
     * Consulta parametrizada para recueprar una cuenta.
     *
     * @param Account $account
     * @return Account|null
     * @throws CloudException
     */
    public function getAccount(Account $account): Account|null
    {
        try {
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
        catch (ConnectException | Exception $e)
        {
            throw new CloudException($e->getMessage(), Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }
}
