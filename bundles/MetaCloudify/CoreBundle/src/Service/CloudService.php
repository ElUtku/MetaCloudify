<?php

namespace MetaCloudify\CoreBundle\Service;

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

use DateTime;
use PHPUnit\Util\Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

use MetaCloudify\CoreBundle\Entity\Metadata;
use MetaCloudify\CoreBundle\Resources\ErrorTypes;
use MetaCloudify\CoreBundle\Entity\Account;
use MetaCloudify\CoreBundle\Resources\FileStatus;

abstract class CloudService
{

    public Filesystem $filesystem;
    public MetaCloudifyLogger $logger;
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
     * @return MetaCloudifyLogger
     */
    public function getLogger(): MetaCloudifyLogger
    {
        return $this->logger;
    }

    /**
     * @param MetaCloudifyLogger $logger
     */
    public function setLogger(MetaCloudifyLogger $logger): void
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
     *
     *  Devuelve todos los elementos que se encuentren en la ruta proporcionada.
     *
     * @param String $path
     * @return DirectoryListing
     * @throws CloudException
     */
    public function listDirectory(String $path): DirectoryListing
    {
        try {
            $filesystem=$this->getFilesystem();

            $path=$this->pathNormalizer->normalizePath($path);
            if(($path!="" || $path!=".") && !$filesystem->directoryExists($path))
            {
                throw new CloudException(ErrorTypes::DIRECTORIO_NO_EXISTE->getErrorMessage(),
                    ErrorTypes::DIRECTORIO_NO_EXISTE->getErrorCode());
            }
            return $filesystem->listContents($path, false);

        } catch (FilesystemException $e) {
            throw new CloudException(ErrorTypes::ERROR_LIST_CONTENT->getErrorMessage().' - '.$e->getMessage(),
                                    ErrorTypes::ERROR_LIST_CONTENT->getErrorCode());
        }
    }

    /**
     *
     *  Crea un directorio.
     *
     * @param String $path
     * @param String $name
     * @return void
     * @throws CloudException
     */
    public function createDir(String $path, String $name): void
    {
        $filesystem=$this->getFilesystem();

        $path=$this->pathNormalizer->normalizePath($path);

        $newPath = $path. '/'. $name;

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

        $path=$this->pathNormalizer->normalizePath($path);

        try {
            if($filesystem->directoryExists($path))
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

            $path=$this->pathNormalizer->normalizePath($path);

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

        $path=$this->pathNormalizer->normalizePath($path);

        if(empty($content->getPathname()))
        {
            throw new CloudException(ErrorTypes::ARCHIVO_LIMITE_TAMANO->getErrorMessage(),
                ErrorTypes::ARCHIVO_LIMITE_TAMANO->getErrorCode());
        }

        $stream = fopen($content->getPathname(), 'r');

        if ($stream) {
            try {
                $filesystem->writeStream($path . "/" . $content->getClientOriginalName(), $stream);
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
     * @param ?UploadedFile $content
     * @return UploadedFile
     * @throws CloudException
     */
    public function getUploadedFile(?UploadedFile $content): UploadedFile
    {
        try {
            if(empty($content))
            {
                throw new CloudException(ErrorTypes::BAD_CONTENT->getErrorMessage(),
                    ErrorTypes::BAD_CONTENT->getErrorCode());
            }

            return $content;
        }catch (Exception $e)
        {
            throw new CloudException(ErrorTypes::BAD_CONTENT->getErrorMessage().' - '.$e->getMessage(),
                ErrorTypes::BAD_CONTENT->getErrorCode());
        }
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

        $path=$this->pathNormalizer->normalizePath($path);

        try {
            $stream = $filesystem->readStream($path);
            $response = new StreamedResponse(function () use ($stream) {
                fpassthru($stream);
                fclose($stream);
            },Response::HTTP_OK);

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
            if ($accounts && array_key_exists($id, $accounts)) {
// Eliminar el elemento del array
                unset($accounts[$id]);
                if (empty($accounts) || !is_array($accounts)) {
// Si está vacío o no es un array, eliminarlo de la sesión
                    $session->remove('accounts');
                }else{
                    $session->set('accounts', $accounts);
                }
            } else
            {
                throw new CloudException(ErrorTypes::ERROR_LOGOUT->getErrorMessage(),
                    ErrorTypes::ERROR_LOGOUT->getErrorCode());
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
            $this->pathNormalizer->normalizePath(dirname($file->path())),
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
     * @param string $path
     * @return StorageAttributes
     * @throws CloudException
     */
    public function getArchivo(string $path): StorageAttributes
    {
        try {
            $filesystem=$this->getFilesystem();

            $path=$this->pathNormalizer->normalizePath($path);

            $contents=$this->listDirectory(dirname($path))->toArray();

            $filteredItems = array_filter($contents, function ($item) use ($path) {
                                $thisItemRuta=$this->pathNormalizer->normalizePath($item['path']);
                                return $thisItemRuta === $path ||
                                        $this->cleanPath($thisItemRuta)==$path;
                            });

            if (!empty($filteredItems)) {
                // El primer elemento encontrado (puede haber más si hay duplicados)
                $item = reset($filteredItems);
                if ($item instanceof FileAttributes) {
                    return new FileAttributes($path, $item->fileSize(), $item->visibility(), $item->lastModified(), $item->mimeType(), $item->extraMetadata());
                } elseif ($item instanceof DirectoryAttributes) {
                    return new DirectoryAttributes($path, $item->visibility(), $item->lastModified(), $item->extraMetadata());
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
     * @return array
     * @throws CloudException
     */
    public function getAnArchive(string $path): array // ruta=a/b/c.txt
    {
        try {
            $filesystem=$this->getFilesystem();

            $path=$this->pathNormalizer->normalizePath($path);

            $contents=$filesystem->listContents(dirname($path),false)->toArray();

            foreach ($contents as $item) {
                if ($this->pathNormalizer->normalizePath($item['path'])==$path ||
                    $this->cleanPath($this->pathNormalizer->normalizePath($item['path'])) === $path) // remote.php/webdav/usuario/a/b/c.txt == /a/b/c.txt
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
    public function copy(Filesystem $source, Filesystem $destination, String $sourcePath, String $destinationPath): void
    {
            $sourcePath=$this->pathNormalizer->normalizePath($sourcePath);
            $destinationPath=$this->pathNormalizer->normalizePath($destinationPath);

            try {
                if($destination->directoryExists($destinationPath))
                {
                    if($destination->fileExists($destinationPath . "/" . basename($sourcePath)))
                    {
                        throw new CloudException(ErrorTypes::FICHERO_YA_EXISTE->getErrorMessage(),
                            ErrorTypes::FICHERO_YA_EXISTE->getErrorCode());
                    } else{

                        try {

                            $content=$source->read($sourcePath);

                            $destination->write($destinationPath . "/" . basename($sourcePath), $content);

                        } catch (FilesystemException | UnableToCreateDirectory $e) {
                            throw new CloudException(ErrorTypes::ERROR_CREAR_FICHERO->getErrorMessage().' - '.$e->getMessage(),
                                ErrorTypes::ERROR_CREAR_FICHERO->getErrorCode());
                        }

                    }
                }else{
                    throw new CloudException(ErrorTypes::DIRECTORIO_NO_EXISTE->getErrorMessage(),
                        ErrorTypes::DIRECTORIO_NO_EXISTE->getErrorCode());
                }

            }catch (FilesystemException | Exception $e) {
                throw new CloudException(ErrorTypes::ERROR_COPY->getErrorMessage().' - '.$e->getMessage(),
                    ErrorTypes::ERROR_COPY->getErrorCode());
            }

    }

    /**
     *
     * Elimina el prefix de webdav y coloca las barras invertidas.
     *
     * @param $path
     * @return string
     */
    function cleanPath($path):string {
        $path = $this->pathNormalizer->normalizePath(
            preg_replace('/^(?:.+?\/)?remote\.php\/dav\/files\/[^\/]+\/(.+)$/', '$1', $path)
        );

        return $path==='.' || $path === '' ? '/' : $path;
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

    /**
     * @param Account $account
     * @return void
     * @throws CloudException
     */
    public abstract function testConection(Account $account): void;


}