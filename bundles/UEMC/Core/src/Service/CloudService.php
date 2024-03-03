<?php

namespace UEMC\Core\Service;

use League\Flysystem\FilesystemException;
use League\Flysystem\Filesystem;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToWriteFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

abstract class CloudService extends UemcLogger
{
    public Filesystem $filesystem;

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
     *
     * Devuelve todos los elementos que se encuentren en la ruta seleccionada.
     *
     * @param Filesystem $filesystem
     * @param $path
     *
     * @return array|string
     */
    public function listDirectory(String $path)
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
            return $e->getMessage();
        }
    }

    /**
     *
     * Crea un directorio.
     *
     * @param Filesystem $filesystem
     * @param $path
     * @param $name
     *
     * @return \Exception|FilesystemException|UnableToCreateDirectory|string
     */
    public function createDir(String $path, String $name)
    {
        $newPath = $path. '/'. $name;

        $this->loggerUEMC->info("Creating Directory " . $newPath);

        $filesystem=$this->getFilesystem();

        try {
            if($filesystem->directoryExists($newPath))
            {
                $message ='El directorio ya existe y por tanto NO ';
            }else{
                $message ='El directorio no existe y ';
                $filesystem->createDirectory($newPath);
            }
        } catch (FilesystemException | UnableToCreateDirectory $exception) {
            return $exception;
        }
        return $message.'Directorio creado';
    }

    /**
     *
     * Crea un fichero de cualquier tipo.
     * NOTE: Webdav requiere de extension.
     *
     * @param Filesystem $filesystem
     * @param $path
     * @param $name
     *
     * @return \Exception|FilesystemException|UnableToCreateDirectory|string
     */
    public function createFile(String $path, String $name)
    {
        $newPath = '/'.$path. '/'. $name;

        $this->loggerUEMC->info("Creating File ".$newPath);

        $filesystem=$this->getFilesystem();

        try {
            if($filesystem->directoryExists($path)) //Comprobamos si existe el directorio
            {
                if($filesystem->directoryExists($newPath))
                {
                    return  "El fichero ya existe";
                } else{
                    $filesystem->write($newPath,"");
                    return  "Fichero creado";
                }
            }else{
                $message ='El directorio no existe ';
                return $message.' | Fichero NO creado';
            }
        } catch (FilesystemException | UnableToCreateDirectory $exception) {
            return $exception;
        }
    }

    /**
     *
     * Elimina ficheros y directorios.
     *
     * @param Filesystem $filesystem
     * @param $path
     *
     * @return \Exception|FilesystemException|UnableToWriteFile|string
     */
    public function delete(String $path)
    {
        $this->loggerUEMC->info("Deleting " . $path);

        $filesystem=$this->getFilesystem();

        try {
            $filesystem->delete($path);
        } catch (FilesystemException | UnableToWriteFile $exception) {
            return $exception;
        }
        return 'Fichero eliminado';
    }

    /**
     *
     * Permite subir archivos de uno en uno.
     *
     * @param Filesystem $filesystem
     * @param $path
     * @param UploadedFile $content
     *
     * @return string
     */
    public function upload(String $path, UploadedFile $content)
    {
        $this->loggerUEMC->info("Uploading ".$content->getPathname());

        $filesystem=$this->getFilesystem();

        $stream = fopen($content->getPathname(), 'r');

        if ($stream) {
            try {
                $filesystem->writeStream($path . "\\" . $content->getClientOriginalName(), $stream);
                return 'OK';
            } catch (FilesystemException | UnableToWriteFile $exception) {
                fclose($stream); // Asegurarse de cerrar el recurso en caso de excepción
                return $exception->getMessage();
            }
        } else {
            return 'Error al abrir el recurso de transmisión.';
        }
    }

    /**
     *
     * Descarga culaquier tipo de archivos.
     *
     * @param Filesystem $filesystem
     * @param $path
     * @param $name
     *
     * @return string|Response
     */
    public function download(String $path, String $name): string|Response
    {

        $filesystem=$this->getFilesystem();

        $this->loggerUEMC->info("Downloading ".$path);

        try {
            $contents = $filesystem->read($path);
            //file_put_contents("/test.mp4", $contents); //Ver si el problema esta aqui
            $mimetype=$filesystem->mimeType($path);

            $response = new Response($contents);

            $response->headers->set('Content-Type', "'$mimetype'");
            //Añadir content lenght
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $name .'"');

            return $response;
        } catch (FilesystemException $e) {
            return new Response($e->getMessage());
        }
    }

    public function logout(SessionInterface $session,Request $request): string
    {
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
        return "Sesion limpia";
    }
}