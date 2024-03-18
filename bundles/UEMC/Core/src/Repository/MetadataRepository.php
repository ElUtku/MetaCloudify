<?php

namespace UEMC\Core\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Exception;

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
    public function upload(Metadata $fileMetadata): void
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
            }
            $em->flush();
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

        $qb->where('m.virtualName = :virtual_name')
            ->setParameter('virtual_name', $file->getVirtualName());

        if (!$file->getVirtualName()) {
            $qb->orWhere('m.path = :path')
                ->andWhere('m.name = :name')
                ->setParameter('path', $file->getPath())
                ->setParameter('name', $file->getName());
        }

        return $qb->getQuery()->getOneOrNullResult();

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
