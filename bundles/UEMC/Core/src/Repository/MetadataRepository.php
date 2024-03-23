<?php

namespace UEMC\Core\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Exception;

use UEMC\Core\Entity\Account;
use UEMC\Core\Entity\Metadata;
use UEMC\Core\Resources\ErrorTypes;
use UEMC\Core\Service\CloudException;

/**
 * @extends EntityRepository<Metadata>
 *
 * @method Metadata|null find($id, $lockMode = null, $lockVersion = null)
 * @method Metadata|null findOneBy(array $criteria, array $orderBy = null)
 * @method Metadata[]    findAll()
 * @method Metadata[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MetadataRepository extends EntityRepository
{

    /**
     * @param Metadata $fileMetadata
     * @return void
     * @throws CloudException
     */
    public function store(Metadata $fileMetadata): void
    {
        try {
            $em=$this->getEntityManager();
            $fileStored=$this->existFile($fileMetadata);
            if($fileStored==null)
            {
                $em->persist($fileMetadata);
            }else{
                $fileStored->setName($fileMetadata->getName());
                $fileStored->setPath($fileMetadata->getPath());
                $fileStored->setVirtualName($fileMetadata->getVirtualName()??null);
                $fileStored->setVirtualPath($fileMetadata->getVirtualPath()??null);
                $fileStored->setAuthor($fileMetadata->getAuthor()??null);
                $fileStored->setLastModified($fileMetadata->getLastModified());
                $fileStored->setVisibility($fileMetadata->getVisibility());
                $fileStored->setStatus($fileMetadata->getStatus());
            }
            $em->flush();
        }catch (Exception | NonUniqueResultException $e)
        {
            throw new CloudException(ErrorTypes::ERROR_LOG_METADATA->getErrorMessage().' - '.$e->getMessage(),
                ErrorTypes::ERROR_LOG_METADATA->getErrorCode());
        }
    }


    /**
     * @param Metadata $fileMetadata
     * @return void
     * @throws CloudException
     */
    public function deleteDirectory(Metadata $fileMetadata): void
    {
        try {
            $dataUnderDirectory=$this->findByPathAndAccount($fileMetadata->getAccount(), $fileMetadata->getPath());
            foreach ($dataUnderDirectory as $file) {
                $file->setLastModified($fileMetadata->getLastModified());
                $file->setStatus($fileMetadata->getStatus());
                $this->store($file);
            }
        }catch (Exception $e)
        {
            throw new CloudException(ErrorTypes::ERROR_DELETE_MULTIPLE_FILES->getErrorMessage().' - '.$e->getMessage(),
                ErrorTypes::ERROR_DELETE_MULTIPLE_FILES->getErrorCode());
        }
    }

    public function fillMetadata(Metadata $file): Metadata
    {
        try {
            $em=$this->getEntityManager();
            $fileStored=$this->existFile($file);
            if($fileStored==null)
            {
                $this->store($file);
            }
            $em->flush();

            return $fileStored??$file;
        }catch (Exception | NonUniqueResultException $e)
        {
            throw new CloudException(ErrorTypes::ERROR_LOG_METADATA->getErrorMessage().' - '.$e->getMessage(),
                ErrorTypes::ERROR_LOG_METADATA->getErrorCode());
        }
    }

    /**
     * @param Metadata $file
     * @return Metadata|null
     * @throws NonUniqueResultException
     */
    private function existFile(Metadata $file): Metadata|null
    {
        $qb = $this->createQueryBuilder('m');

        $qb->where('m.path = :path')
            ->andWhere('m.name = :name')
            ->andWhere('m.account = :account')
            ->andWhere('m.type = :type')
            ->setParameter('path', $file->getPath())
            ->setParameter('name', $file->getName())
            ->setParameter('account', $file->getAccount())
            ->setParameter('type', $file->getType());

        return $qb->getQuery()->getOneOrNullResult();

    }

    /**
     * @param Account $account
     * @param String $path
     * @return float|int|mixed|string
     */
    private function findByPathAndAccount(Account $account, String $path): mixed
    {
        $qb = $this->createQueryBuilder('m');

        $qb->where('m.path LIKE :path')
            ->andWhere('m.account = :account')
            ->setParameter('path', $path . '%')
            ->setParameter('account', $account);

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return Metadata[] Returns an array of Metadata objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Metadata
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
