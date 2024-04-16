<?php

namespace UEMC\Core\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Exception;

use UEMC\Core\Entity\Account;
use UEMC\Core\Entity\Metadata;
use UEMC\Core\Resources\ErrorTypes;
use UEMC\Core\Resources\FileStatus;
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
                $fileStored->setSize($fileMetadata->getSize()??null);
                $fileStored->setMimeType($fileMetadata->getMimeType()??null);
                $fileStored->setAuthor($fileMetadata->getAuthor()??null);
                $fileStored->setLastModified($fileMetadata->getLastModified());
                $fileStored->setVisibility($fileMetadata->getVisibility());
                $fileStored->setStatus($fileMetadata->getStatus());
                $fileStored->setExtra($fileMetadata->getExtra()??null);
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


    /**
     * @param Metadata $destiantionMetadataFile
     * @param Metadata $originalCloudMetadataFile
     * @return void
     * @throws CloudException
     */
    public function copyMetadata(Metadata $destiantionMetadataFile, Metadata $originalCloudMetadataFile): void
    {
        $destiantionMetadataFile->setStatus(FileStatus::NEW->value);
        $destiantionMetadataFile->setExtra($originalCloudMetadataFile->getExtra());
        $destiantionMetadataFile->setAuthor($originalCloudMetadataFile->getAuthor());

        $this->store($destiantionMetadataFile);
    }

    /**
     * @param Metadata $file
     * @return Metadata
     * @throws CloudException
     */
    public function getCloudMetadata(Metadata $file): Metadata
    {
        try {
            $fileStored=$this->existFile($file);

            if($fileStored!=null)
            {
                $fileStored->setVisibility($fileStored->getVisibility() ?? $file->getVisibility());
                $fileStored->setVirtualName($fileStored->getVirtualName() ?? $file->getVirtualName());
                $fileStored->setVirtualPath($fileStored->getVirtualPath() ?? $file->getVirtualPath());
                $fileStored->setSize($fileStored->getSize()??$file->getSize());
                $fileStored->setMimeType($fileStored->getMimeType()??$file->getMimeType());
                $fileStored->setAuthor($fileStored->getAuthor() ?? $file->getAuthor());
                $fileStored->setStatus($fileStored->getStatus() ?? $file->getStatus());
            }

            return $fileStored??$file;
        }catch (Exception | NonUniqueResultException $e)
        {
            throw new CloudException(ErrorTypes::ERROR_GET_METADATA->getErrorMessage().' - '.$e->getMessage(),
                ErrorTypes::ERROR_GET_METADATA->getErrorCode());
        }
    }

    /**
     * @param Metadata $file
     * @return Metadata|null
     * @throws NonUniqueResultException
     */
    public function existFile(Metadata $file): Metadata|null
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
    public function findByPathAndAccount(Account $account, String $path): mixed
    {
        $qb = $this->createQueryBuilder('m');

        $qb->where('m.path LIKE :path')
            ->andWhere('m.account = :account')
            ->setParameter('path', $path . '%')
            ->setParameter('account', $account);

        return $qb->getQuery()->getResult();
    }


    /**
     * @param Account $account
     * @param String $path
     * @param String $name
     * @return Metadata|null
     * @throws NonUniqueResultException
     */
    public function findByExactPathAndAccountNull(Account $account, String $path, String $name): Metadata|null
    {
        $path=($path === '.') ? '' : $path; // Si el directorio es '' dirname suiele devolver '.' y hay que limpiarlo

        $qb = $this->createQueryBuilder('m');
        $qb->where('m.path = :path')
            ->andWhere('m.name = :name')
            ->andWhere('m.account = :account')
            ->setParameter('path', $path)
            ->setParameter('name', $name)
            ->setParameter('account', $account);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
