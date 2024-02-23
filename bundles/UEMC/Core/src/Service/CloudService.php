<?php

namespace UEMC\Core\Service;

use League\Flysystem\FilesystemException;
use League\Flysystem\Filesystem;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToWriteFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class CloudService
{

    /**
     *
     * Devuelve todos los elementos que se encuentren en la ruta seleccionada.
     *
     * @param Filesystem $filesystem
     * @param $path
     *
     * @return array|string
     */
    public function listDirectory(Filesystem $filesystem, $path)
    {
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
    public function createDir(Filesystem $filesystem, $path, $name)
    {
        $newPath = $path. '/'. $name;

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
    public function createFile(Filesystem $filesystem, $path, $name)
    {
        $newPath = '/'.$path. '/'. $name;
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
    public function delete(Filesystem $filesystem, $path)
    {
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
    public function upload(Filesystem $filesystem, $path, UploadedFile $content)
    {
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
    public function download(Filesystem $filesystem, $path, $name)
    {
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
            return $e->getMessage();
        }
    }
}