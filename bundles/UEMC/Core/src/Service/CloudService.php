<?php

namespace UEMC\Core\Service;

use DateTime;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ObjectManager;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemException;
use League\Flysystem\Filesystem;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\PathNormalizer;
use PHPUnit\Util\Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

use UEMC\Core\Entity\Metadata;
use UEMC\Core\Resources\ErrorTypes;
use UEMC\Core\Entity\Account;
use UEMC\Core\Resources\FileStatus;

abstract class CloudService
{

    public Filesystem $filesystem;
    public UemcLogger $logger;
    public PathNormalizer $pathNormalizer;


    /**
     * @return Filesystem
     */
    public function getFilesystem(): Filesystem
    {
        return $this->filesystem;
    }

    /**
     * @param Filesystem $filesystem
     */
    public function setFilesystem(Filesystem $filesystem): void
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @return UemcLogger
     */
    public function getLogger(): UemcLogger
    {
        return $this->logger;
    }

    /**
     * @param UemcLogger $logger
     */
    public function setLogger(UemcLogger $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @return PathNormalizer
     */
    public function getPathNormalizer(): PathNormalizer
    {
        return $this->pathNormalizer;
    }

    /**
     * @param PathNormalizer $pathNormalizer
     */
    public function setPathNormalizer(PathNormalizer $pathNormalizer): void
    {
        $this->pathNormalizer = $pathNormalizer;
    }



    /**
     *  Devuelve todos los elementos que se encuentren en la ruta seleccionada.
     *
     * @param String $path
     * @return DirectoryListing
     * @throws CloudException
     */
    public function listDirectory(String $path): DirectoryListing
    {
        try {
            $filesystem=$this->getFilesystem();
            return $filesystem->listContents($path, false);

        } catch (FilesystemException $e) {
            throw new CloudException(ErrorTypes::ERROR_LIST_CONTENT->getErrorMessage().' - '.$e->getMessage(),
                                    ErrorTypes::ERROR_LIST_CONTENT->getErrorCode());
        }
    }

    /**
     *  Crea un directorio.
     *
     * @param String $path
     * @param String $name
     * @return void
     * @throws CloudException
     */
    public function createDir(String $path, String $name): void
    {
        $newPath = $path. '/'. $name;

        $filesystem=$this->getFilesystem();

        try {
            if($filesystem->directoryExists($newPath))
            {
                throw new CloudException(ErrorTypes::DIRECTORY_YA_EXISTE->getErrorMessage(),
                    ErrorTypes::DIRECTORY_YA_EXISTE->getErrorCode());
            }else{
                $filesystem->createDirectory($newPath);
            }
        } catch (FilesystemException | UnableToCreateDirectory $e) {
            throw new CloudException(ErrorTypes::ERROR_CREAR_DIRECTORIO->getErrorMessage().' - '.$e->getMessage(),
                ErrorTypes::ERROR_CREAR_DIRECTORIO->getErrorCode());
        }
    }

    /**
     *  Crea un fichero de cualquier tipo.
     *
     * @param String $path
     * @param String $name
     * @return void
     * @throws CloudException
     */
    public function createFile(String $path, String $name): void
    {

        $filesystem=$this->getFilesystem();

        try {
            if($filesystem->directoryExists($path)) //Comprobamos si existe el directorio
            {
                if($filesystem->fileExists($path . '/' . $name))
                {
                    throw new CloudException(ErrorTypes::FICHERO_YA_EXISTE->getErrorMessage(),
                        ErrorTypes::FICHERO_YA_EXISTE->getErrorCode());
                } else{
                    $filesystem->write($path . '/' . $name,'');
                }
            }else{
                throw new CloudException(ErrorTypes::DIRECTORIO_NO_EXISTE->getErrorMessage(),
                    ErrorTypes::DIRECTORIO_NO_EXISTE->getErrorCode());
            }
        } catch (FilesystemException | UnableToCreateDirectory $e) {
            throw new CloudException(ErrorTypes::ERROR_CREAR_FICHERO->getErrorMessage().' - '.$e->getMessage(),
                ErrorTypes::ERROR_CREAR_FICHERO->getErrorCode());
        }
    }

    /**
     *  Elimina ficheros y directorios.
     *
     * @param String $path
     * @return void
     * @throws CloudException
     */
    public function delete(String $path): void
    {
        try {
            $filesystem=$this->getFilesystem();

            $archivo=$this->getArchivo($path);
            if($archivo->isDir())
            {
                $filesystem->deleteDirectory($path);
            } else if($archivo->isFile()){
                $filesystem->delete($path);
            }

        } catch (FilesystemException | UnableToDeleteFile | UnableToDeleteDirectory $e) {
            throw new CloudException(ErrorTypes::ERROR_BORRAR->getErrorMessage().' - '.$e->getMessage(),
                ErrorTypes::ERROR_BORRAR->getErrorCode());
        }
    }

    /**
     *  Permite subir archivos de uno en uno.
     *
     * @param String $path
     * @param UploadedFile $content
     * @return void
     * @throws CloudException
     */
    public function upload(String $path, UploadedFile $content): void
    {
        $filesystem=$this->getFilesystem();

        $stream = fopen($content->getPathname(), 'r');

        if ($stream) {
            try {
                $filesystem->writeStream($path . "\\" . $content->getClientOriginalName(), $stream);
            } catch (FilesystemException | UnableToWriteFile $e) {
                fclose($stream); // Asegurarse de cerrar el recurso en caso de excepción
                throw new CloudException(ErrorTypes::ERROR_UPLOAD->getErrorMessage().' - '.$e->getMessage(),
                    ErrorTypes::ERROR_UPLOAD->getErrorCode());
            }
        } else {
            throw new CloudException(ErrorTypes::BAD_CONTENT->getErrorMessage(),
                ErrorTypes::BAD_CONTENT->getErrorCode());
        }
    }

    /**
     * @param UploadedFile $content
     * @return UploadedFile
     */
    public function getUploadedFile(UploadedFile $content): UploadedFile
    {
        return $content;
    }

    /**
     *  Descarga culaquier tipo de archivos.
     *
     * @param String $path
     * @param String $name
     * @return Response
     * @throws CloudException
     */
    public function download(String $path, String $name): Response
    {

        $filesystem=$this->getFilesystem();

        try {
            $stream = $filesystem->readStream($path);
            $response = new StreamedResponse(function () use ($stream) {
                fpassthru($stream);
                fclose($stream);
            });

            $response->headers->set('Content-Type', $filesystem->mimeType($path));
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $name . '"');

            return $response;
        } catch (FilesystemException | \Exception $e) {
            throw new CloudException(ErrorTypes::ERROR_DESCARGA->getErrorMessage().' - '.$e->getMessage(),
                ErrorTypes::ERROR_DESCARGA->getErrorCode());
        }
    }

    /**
     * @param SessionInterface $session
     * @param Request $request
     * @return void
     * @throws CloudException
     */
    public function logout(SessionInterface $session, Request $request): void
    {
        try {
            $accounts = $session->get('accounts');

            $id = $request->get('accountId');
            if (array_key_exists($id, $accounts)) {
// Eliminar el elemento del array
                unset($accounts[$id]);
                if (empty($accounts) || !is_array($accounts)) {
// Si está vacío o no es un array, eliminarlo de la sesión
                    $session->remove('accounts');
                }else{
                    $session->set('accounts', $accounts);
                }
            }
        }catch (Exception $e){
            throw new CloudException(ErrorTypes::ERROR_LOGOUT->getErrorMessage().' - '.$e->getMessage(),
                ErrorTypes::ERROR_LOGOUT->getErrorCode());
        }
    }

    /**
     * @param SessionInterface $session
     * @param Account $account
     * @return string
     * @throws CloudException
     */
    public function setSession(SessionInterface $session, Account $account): string
    {
        $accounts=$session->get('accounts');

        try{
            if(!empty($accounts)){ //Si el array no esta vacio se comprueba
                foreach ($accounts as $accountId => $acc)
                {
                    if (
                        (!empty($acc['openid']) and $acc['openid'] == $account->getOpenid()) or
                        (
                            !empty($acc['URL']) and !empty($acc['user']) and
                            $acc['URL'] == $account->getURL() and $acc['user'] == $account->getUser()
                        )
                    ) {
                        return $accountId;
                    }
                }
            }

//Si no se encuetra la cuenta se agrega
            $accountId=uniqid();
            $accounts[$accountId]=get_object_vars($account);
            $session->set('accounts',$accounts);
            return $accountId;

        }catch (\Exception $e){
            throw new CloudException(ErrorTypes::ERROR_SAVE_SESSION->getErrorMessage().' - '.$e->getMessage(),
                ErrorTypes::ERROR_SAVE_SESSION->getErrorCode());
        }
    }

    /**
     * @param SessionInterface $session
     * @param Request $request
     * @return Account | String
     * @throws CloudException
     */
    public function loginPost(SessionInterface $session, Request $request): Account | String
    {
        return $this->login($session, $request);
    }

    /**
     * @param StorageAttributes $file
     * @param Account $account
     * @return Metadata
     */
    public function getBasicMetadata(StorageAttributes $file, Account $account):Metadata
    {

        if ($file instanceof FileAttributes)
        {
            $fileSize = $file->fileSize();
            $mimeType = $file->MimeType();
        }

        $visibility = $file->Visibility();
        $lastModified = $file->lastModified();

        $lastModifiedTimestamp = $lastModified;
        $dateTime = new DateTime();
        $dateTime->setTimestamp($lastModifiedTimestamp);

        return new Metadata(
            basename($file->path()),
            $file->extraMetadata()['id']??null,
            dirname($file->path()),
            $file->extraMetadata()['virtual_path']??null,
            $file->type(),
            $fileSize??null,
            $mimeType??null,
            $dateTime,
            null,
            $visibility,
            FileStatus::EXISTENT->value,
            null,
            $account
        );
    }

    /**
     * @param string $ruta
     * @return StorageAttributes
     * @throws CloudException
     */
    public function getArchivo(string $ruta): StorageAttributes
    {
        try {
            $filesystem=$this->getFilesystem();
            $ruta=$this->pathNormalizer->normalizePath($ruta);

            $contents=$filesystem->listContents(dirname($ruta),false)->toArray();

            $filteredItems = array_filter($contents, function ($item) use ($ruta) {
                                $thisItemRuta=$this->pathNormalizer->normalizePath($item['path']);
                                return $thisItemRuta === $ruta ||
                                        $this->cleanOwncloudPath($thisItemRuta)==$ruta;
                            });

            if (!empty($filteredItems)) {
                // El primer elemento encontrado (puede haber más si hay duplicados)
                $item = reset($filteredItems);
                if ($item instanceof FileAttributes) {
                    return new FileAttributes($ruta, $item->fileSize(), $item->visibility(), $item->lastModified(), $item->mimeType(), $item->extraMetadata());
                } elseif ($item instanceof DirectoryAttributes) {
                    return new DirectoryAttributes($ruta, $item->visibility(), $item->lastModified(), $item->extraMetadata());
                }
            }
//Si $filteredItems esta vacio es porque hay un error
            throw new Exception();
        } catch (FilesystemException | Exception $e) {

            throw new CloudException(ErrorTypes::ERROR_GET_NATIVE_METADATA->getErrorMessage().' - '.$e->getMessage(),
                ErrorTypes::ERROR_GET_NATIVE_METADATA->getErrorCode());
        }
    }

    /**
     * @param string $ruta
     * @return array
     * @throws CloudException
     */
    public function getAnArchive(string $ruta): array // ruta=a/b/c.txt
    {
        try {
            $filesystem=$this->getFilesystem();

            $contents=$filesystem->listContents(dirname($ruta),false)->toArray();

            foreach ($contents as $item) {
                if ($item['path']==$ruta ||
                    str_replace('\\', '/',$item['path']) == $ruta ||
                    $this->cleanOwncloudPath($item['path']) == $ruta) // remote.php/webdav/usuario/a/b/c.txt == /a/b/c.txt
                {
                    return json_decode(json_encode($item),true); //El objeto se convierte a un array
                }
            }

            throw new Exception();
        } catch (FilesystemException | Exception $e) {
            throw new CloudException(ErrorTypes::ERROR_GET_NATIVE_METADATA->getErrorMessage().' - '.$e->getMessage(),
                ErrorTypes::ERROR_GET_NATIVE_METADATA->getErrorCode());
        }
    }

    /**
     * @param $item
     * @return StorageAttributes
     * @throws CloudException
     */
    public function getTypeOfArchive($item) : StorageAttributes
    {
        if ($item instanceof FileAttributes) {
            return new FileAttributes($item->path(), $item->fileSize(), $item->visibility(), $item->lastModified(), $item->mimeType(), $item->extraMetadata());
        } elseif ($item instanceof DirectoryAttributes) {
            return new DirectoryAttributes($item->path(), $item->visibility(), $item->lastModified(), $item->extraMetadata());
        } else
        {
            throw new CloudException(ErrorTypes::ERROR_GET_NATIVE_METADATA->getErrorMessage(),
                ErrorTypes::ERROR_GET_NATIVE_METADATA->getErrorCode());
        }
    }

    /**
     *
     * Copia un archivo independientemente del tipo del tipo de nube.
     *
     * @param Filesystem $source
     * @param Filesystem $destination
     * @param String $sourcePath
     * @param String $destinationPath
     * @return void
     * @throws CloudException
     */
    public function onlyCopy(Filesystem $source, Filesystem $destination, String $sourcePath, String $destinationPath): void
    {
        try {
            $this->setFilesystem($source);
            $content=$source->read($sourcePath);

            $this->setFilesystem($destination);
            $destination->write($destinationPath . "\\" . basename($sourcePath), $content);
        }catch (FilesystemException | Exception $e) {
            throw new CloudException(ErrorTypes::ERROR_COPY->getErrorMessage().' - '.$e->getMessage(),
                ErrorTypes::ERROR_COPY->getErrorCode());
        }

    }

    /**
     *
     * Copia un archivo y sus metadatos de la aplicaicon.
     * Es necesario porporcionar un array con los Filesystem destino y origen asi como sus cuantas asociadas
     * Es necesario porporiconar las rutas de origen y destino
     *
     * @param array $filesSystems
     * @param ObjectManager $entityManager
     * @param String $sourceFullPath
     * @param String $destinationDirectoryPath
     * @param String $destinationFullPath
     * @return void
     * @throws CloudException
     */
    public function copyWithMetadata(array $filesSystems, ObjectManager $entityManager, String $sourceFullPath, String $destinationDirectoryPath, String $destinationFullPath): void
    {
        $sourceFileSystem=$filesSystems['sourceFileSystem'];
        $destinationFileSystem=$filesSystems['destinationFileSystem'];
        $sourceAccount=$filesSystems['sourceAccount'];
        $destinationAccount=$filesSystems['destinationAccount'];

        try {
            $sourceAccountBD = $entityManager->getRepository(Account::class)->getAccount($sourceAccount);
            $destinationAccountBD=$entityManager->getRepository(Account::class)->getAccount($destinationAccount);
        } catch (NonUniqueResultException $e) {
            throw new CloudException(ErrorTypes::ERROR_OBTENER_USUARIO->getErrorMessage().' - '.$e->getMessage(),
                ErrorTypes::ERROR_OBTENER_USUARIO->getErrorCode());
        }

//Obtenemos los metadatos del archivo original
        $this->setFilesystem($sourceFileSystem);
        $originalFile=$this->getArchivo($sourceFullPath);
        $originalMetadataFile=$this->getBasicMetadata($originalFile,$sourceAccountBD);
        $originalCloudMetadataFile=$entityManager->getRepository(Metadata::class)->getCloudMetadata($originalMetadataFile);

//Copiamos a la cuenta destino el archivo
        $this->onlyCopy($sourceFileSystem,$destinationFileSystem,$sourceFullPath,$destinationDirectoryPath);

//Copiamos los metadatos del archivo original al de destino
        $this->setFilesystem($destinationFileSystem);
        $destiantionFile=$this->getArchivo($destinationFullPath);
        $destiantionMetadataFile=$this->getBasicMetadata($destiantionFile,$destinationAccountBD);
        $entityManager->getRepository(Metadata::class)->copyMetadata($destiantionMetadataFile,$originalCloudMetadataFile);
    }

    /**
     * @param $path
     * @return string
     */
    function cleanOwncloudPath($path):string {
        return preg_replace('/.*\/remote\.php\/dav\/files\/\w+\//', '', $path);
    }

    /**
     * @param SessionInterface $session
     * @param Request $request
     * @return Account
     * @throws CloudException
     */
    public abstract function login(SessionInterface $session, Request $request): Account;

    /**
     * @param Account $account
     * @return Filesystem
     * @throws CloudException
     */
    public abstract function constructFilesystem(Account $account): Filesystem;


}