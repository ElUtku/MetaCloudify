<?php

namespace UEMC\Core\Service;

use League\Flysystem\FilesystemException;
use League\Flysystem\Filesystem;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\FileAttributes;
use League\Flysystem\DirectoryAttributes;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use UEMC\Core\Entity\Metadata;
use UEMC\Core\Resources\ErrorTypes;
use UEMC\Core\Entity\Account;

abstract class CloudService
{

    public Filesystem $filesystem;
    public UemcLogger $logger;

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
     *  Devuelve todos los elementos que se encuentren en la ruta seleccionada.
     *
     * @param String $path
     * @return array
     * @throws CloudException
     */
    public function listDirectory(String $path): array
    {

        $filesystem=$this->getFilesystem();

        try {
            $contents = $filesystem->listContents($path ?? '', false);
            $contenido=[];
            foreach ($contents as $item) {
                $contenido[] = $item;
            }
            return $contenido;

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
        $newPath = '/'.$path. '/'. $name;

        $filesystem=$this->getFilesystem();

        try {
            if($filesystem->directoryExists($path)) //Comprobamos si existe el directorio
            {
                if($filesystem->directoryExists($newPath))
                {
                    throw new CloudException(ErrorTypes::FICHERO_YA_EXISTE->getErrorMessage(),
                        ErrorTypes::FICHERO_YA_EXISTE->getErrorCode());
                } else{
                    $filesystem->write($newPath,"");
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
        $this->logger->info("Deleting " . $path);

        $filesystem=$this->getFilesystem();

        try {
            $filesystem->delete($path);
        } catch (FilesystemException | UnableToWriteFile $e) {
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
        $this->logger->info("Uploading ".$content->getPathname());

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

        $this->logger->info("Downloading ".$path);

        try {
            $contents = $filesystem->read($path);
            //file_put_contents("/test.mp4", $contents); //Ver si el problema esta aqui
            $mimetype=$filesystem->mimeType($path);

            $response = new Response($contents);

            $response->headers->set('Content-Type', "'$mimetype'");
            //Añadir content lenght
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $name .'"');

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
        }catch (\Exception $e){
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
     * @return Account
     * @throws CloudException
     */
    public function loginPost(SessionInterface $session, Request $request): Account
    {
        $account=$this->login($session, $request);
        return $account;
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
     * @param String $path
     * @return Metadata
     * @throws CloudException
     */
    public function getNativeMetadata(String $path):Metadata
    {
        try {

            $attributes = match ($this->distinguirTipoRuta($path)) {
                'file' => new FileAttributes($path),
                'dir' => new DirectoryAttributes($path),
                default => throw new CloudException(ErrorTypes::NO_SUCH_FILE_OR_DIRECTORY->getErrorMessage(),
                    ErrorTypes::NO_SUCH_FILE_OR_DIRECTORY->getErrorCode()),
            };

            $this->logger->debug("path->: ".$path);
            $this->logger->debug("isFile()->: ".$attributes->isFile());
            $this->logger->debug("isFile()->: ".$attributes->type());

            $extraMetadata= $attributes->extraMetadata();
            $this->logger->debug("extraMetadata: ".json_encode($extraMetadata));
            return new Metadata(null,$extraMetadata['id']??null,$path,$extraMetadata['virtual_path']??null,$attributes->type(),$attributes->lastModified()??new \DateTime(),null,$attributes->visibility(),null,null);

        } catch (FilesystemException | \Exception $e) {
            throw new CloudException(ErrorTypes::ERROR_GET_NATIVE_METADATA->getErrorMessage().' - '.$e->getMessage(),
                ErrorTypes::ERROR_GET_NATIVE_METADATA->getErrorCode());
        }

    }

    /**
     * @param string $ruta
     * @return string
     * @throws CloudException
     */
    public function distinguirTipoRuta(string $ruta): string
    {
        try {
            if ($this->getFilesystem()->has($ruta)) {
                if ($this->getFilesystem()->fileExists($ruta)) {
                    return 'file';
                } else {
                    return 'dir';
                }
            } else {
                return 'KO';
            }
        } catch (FilesystemException | \Exception $e) {
            throw new CloudException(ErrorTypes::ERROR_GET_NATIVE_METADATA->getErrorMessage().' - '.$e->getMessage(),
                ErrorTypes::ERROR_GET_NATIVE_METADATA->getErrorCode());
        }
    }

}