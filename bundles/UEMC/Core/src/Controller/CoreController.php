<?php

namespace UEMC\Core\Controller;

use DateTime;
use Exception;
use Doctrine\Persistence\ManagerRegistry;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

use League\Flysystem\WhitespacePathNormalizer;

use UEMC\Core\Entity\Account;
use UEMC\Core\Entity\Metadata;
use UEMC\Core\Resources\ErrorTypes;
use UEMC\Core\Resources\FileStatus;
use UEMC\Core\Service\CloudException;
use UEMC\Core\Service\CloudService as Core;
use UEMC\Core\Service\UemcLogger;
use UEMC\OwnCloud\Service\CloudService as OwnCloudCore;
use UEMC\Ftp\Service\CloudService as FtpCore;
use UEMC\GoogleDrive\Service\CloudService as GoogleDriveCore;
use UEMC\OneDrive\Service\CloudService as OneDriveCore;


class CoreController extends AbstractController
{

    private Account $account;
    private Core $core;

    /**
     *
     * Se escoge un tipo de core.
     *
     * @param string $cloud
     * @return void
     * @throws CloudException
     */
    private function createContext(string $cloud): void
    {
        $this->core = match ($cloud) {
            'onedrive' => new OneDriveCore(),
            'googledrive' => new GoogleDriveCore(),
            'owncloud' => new OwnCloudCore(),
            'ftp' => new FtpCore(),
            default => throw new CloudException(ErrorTypes::ERROR_CONTROLLER->getErrorMessage(),
                                                ErrorTypes::ERROR_CONTROLLER->getErrorCode()),
        };
        $this->core->setLogger(new UemcLogger());
        $this->core->setPathNormalizer(new WhitespacePathNormalizer());
        $this->account=new Account();
    }

    /**
     *
     * Se recupera el filesystem si ya existe en sesión
     *
     * @param SessionInterface $session
     * @param Request $request
     * @return void
     * @throws CloudException
     */
    private function retriveCore(SessionInterface $session, Request $request): void
    {
        $ruta=$request->attributes->get('_route');
        $accountId = $request->get('accountId') ??
                     $request->get('accountId1') ?? null;

        if($session->has('accounts') and $ruta !== 'login' and $ruta !== 'login_token' and $ruta !== 'loginWeb' )
        {
            $this->account = $this->core->arrayToObject($session->get('accounts')[$accountId]);
            $filesystem = $this->core->constructFilesystem($this->account);
            $this->core->setFilesystem($filesystem);
        }
    }


    /**
     *
     *  Se recupera las dos cuentas y se devuelven junto a su flysystem
     *
     * @param SessionInterface $session
     * @param Request $request
     * @param String $cloud1
     * @param String $cloud2
     * @return array
     * @throws CloudException
     */
    private function retriveMultiFileSystem(SessionInterface $session, Request $request, String $cloud1, String $cloud2): array
    {
        $ruta=$request->attributes->get('_route');
        $accountId1 = $request->query->get('accountId1') ?? $request->request->get('accountId1') ?? null;
        $accountId2 = $request->query->get('accountId2') ?? $request->request->get('accountId2') ?? null;

        if($session->has('accounts') and $ruta !== 'login' and $ruta !== 'login_token' and $ruta !== 'loginWeb' )
        {
            // ACCOUNT 1
            $this->createContext($cloud1);
            $account1 = $this->core->arrayToObject($session->get('accounts')[$accountId1]);
            $filesystem1 = $this->core->constructFilesystem($account1);

            // ACCOUNT 2
            $this->createContext($cloud2);
            $account2 = $this->core->arrayToObject($session->get('accounts')[$accountId2]);
            $filesystem2 = $this->core->constructFilesystem($account2);

            return [
                'sourceFileSystem'=>$filesystem1,
                'sourceAccount'=>$account1,
                'destinationFileSystem'=>$filesystem2,
                'destinationAccount'=>$account2];
        } else
        {
            throw new Exception();
        }
    }

    /**
     *
     *  Login generico para autenticarse via web.
     *
     * @Route("/{cloud}/login", name="login", methods={"GET","POST"}) //GET es usado para solicitar la url OAUTH el resto de peticiones van por POST
     */
    public function login(ManagerRegistry $doctrine, SessionInterface $session, Request $request, string $cloud): Response
    {

        try {
            $entityManager = $doctrine->getManager();

            $this->createContext($cloud);
            $this->retriveCore($session,$request);

            $account=$this->core->login($session,$request);

            $accountExists = $entityManager->getRepository(Account::class)->loggin($account);

            $account->setId($accountExists->getId());

            $accountId=$this->core->setSession($session,$account);

            $this->core->logger->info('LOGGIN | '.'AccountId:'.$accountId.' | id: '.
                $accountExists->getId().' | controller: '.$account->getCloud().
                ' | user:' . $account->getUser());

        }catch (CloudException $e)
        {
            $this->addFlash('error','CODE: '.$e->getStatusCode(). ' - MESSAGE: '.$e->getMessage());

            $this->core->logger->warning('LOGGIN ERROR | '.$e->getMessage());
        }
        return $this->redirectToRoute('_home_index');
    }

    /**
     *
     *  Este login debe ser usado como endpoint para obtener el identificador de la cuenta en la sesion.
     *
     * @Route("/{cloud}/login/token", name="login_token", methods={"GET","POST"})
     */
    public function loginPost(ManagerRegistry $doctrine, SessionInterface $session, Request $request, string $cloud): Response
    {
        try {
            $entityManager = $doctrine->getManager();

            $this->createContext($cloud);
            $this->retriveCore($session,$request);

            $result=$this->core->loginPost($session,$request);

            if($result instanceof Account)
            {
                $accountExists=$entityManager->getRepository(Account::class)->loggin($result);

                $result->setId($accountExists->getId());
                $accountId=$this->core->setSession($session,$result);

                $this->core->logger->info('LOGGIN | '.'AccountId:'.$accountId.' | id: '.
                    $accountExists->getId().' | controller: '.$result->getCloud().
                    ' | user:' . $result->getUser());

                return new JsonResponse('El identificador es ' .$accountId);
            } else
            {
                return new JsonResponse($result);
            }

        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getStatusCode());
        }
    }

    /**
     *
     *  Este login proporciona una interfaz web para aquellas nubes que necesiten autentitcacion básica
     *
     * @Route("/{cloud}/login/web", name="login_web", methods={"GET"})
     */
    public function loginWeb(string $cloud) : Response
    {
        return match ($cloud) {
            'owncloud' => $this->render('@UEMCOwnCloudBundle/login.html.twig'),
            'ftp' => $this->render('@UEMCFtpBundle/login.html.twig'),
            default => new JsonResponse(ErrorTypes::ERROR_INDETERMINADO->getErrorMessage(),
                ErrorTypes::ERROR_INDETERMINADO->getErrorCode()),
        };
    }

    /**
     *
     *  Elimina una cuenta de la sesión.
     *
     * @Route("/{cloud}/logout", name="logout", methods={"GET"})
     */
    public function logout(SessionInterface $session, Request $request, string $cloud): Response
    {
        try {
            $this->createContext($cloud);
            $this->retriveCore($session,$request);
            $this->core->logout($session,$request);

            $this->core->logger->info('LOGOUT | '.' | id: '.
                $this->account->getCloud().
                ' | user:' . $this->account->getUser());
            return new JsonResponse();
        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getStatusCode());
        }
    }

    /**
     *
     * Lista todos los archvios de una ruta.
     *
     * @Route("/{cloud}/drive", name="drive", methods={"GET"})
     */
    public function drive(ManagerRegistry $doctrine, SessionInterface $session, Request $request, string $cloud): Response
    {
        try {
            $entityManager = $doctrine->getManager();

            $this->createContext($cloud);
            $this->retriveCore($session,$request);

            $path=$request->get('path')??$request->get('destinationPath');

            $contentInDirectory=$this->core->listDirectory($path);
            $contentInDirectoryArray=$contentInDirectory->toArray();

            $account = $entityManager->getRepository(Account::class)->getAccount($this->account);

            // ------- Se añade a cada archivo sus metadatos (si los tiene) ------
            foreach ($contentInDirectoryArray as $archive)
            {
                $item=json_decode(json_encode($archive),true); //Se convierte a un objeto modificable
                $path=$item['path'];

                // ---- Si el archivo no esta registrado en nuestra base de datos, se ignora
                if ($entityManager->getRepository(Metadata::class)->findByExactPathAndAccountNull($account,dirname($path),basename($path)))
                {

                    $archivoTipado=$this->core->getTypeOfArchive($archive); //Se define si es carpeta o fichero
                    $metadata = $this->core->getBasicMetadata($archivoTipado,$account);

                    $extraMetadata=$entityManager->getRepository(Metadata::class)->getCloudMetadata($metadata);

                    $item['extra_metadata'] = [
                        'virtual_name' => $extraMetadata->getVirtualName(),
                        'virtual_path' => $extraMetadata->getVirtualPath(),
                        'author' => $extraMetadata->getAuthor(),
                        'visibility' => $extraMetadata->getVisibility(),
                        'status' => $extraMetadata->getStatus(),
                        'extra' => $extraMetadata->getExtra(),
                    ];
                }

                $archivesWhitMetadata[]=$item;
            }

            $this->core->logger->info('DRIVE | '.' | id: '.
                $account->getId().' | controller: '.$account->getCloud().
                ' | user:' . $account->getUser());

            return new JsonResponse($archivesWhitMetadata??$contentInDirectoryArray,Response::HTTP_OK);

        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getStatusCode());
        }

    }

    /**
     *
     * Descarga el archivo pasado en la ruta
     *
     * @Route("/{cloud}/drive/download", name="download", methods={"GET"})
     */
    public function download(SessionInterface $session, Request $request, string $cloud): Response
    {
        try {
            $this->createContext($cloud);
            $this->retriveCore($session,$request);

            $path=$request->get('path');
            $name=$request->get('name');

            $this->core->logger->info('DOWNLOAD | '.' file: '.$path.'\\'.$name.
               ' | controller: '.$this->account->getCloud().
                ' | user:' . $this->account->getUser());

            return $this->core->download($path,$name); // Tipo Resonse
        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getStatusCode());
        }
    }

    /**
     *
     * Crea una carpeta con el nombre y en la ruta especificados
     *
     * @Route("/{cloud}/drive/createDir", name="createDir", methods={"POST"})
     */
    public function createDir(ManagerRegistry $doctrine, SessionInterface $session, Request $request, string $cloud): Response
    {
        try {
            $entityManager = $doctrine->getManager();

            $name=$request->get('name');
            $path=$request->get('path');

            $this->createContext($cloud);
            $this->retriveCore($session,$request);
            $this->core->createDir($path,$name);

            $entityManager->getRepository(Metadata::class)->store(
                new Metadata(
                    $name,
                    null,
                    $path,
                    null,
                    'dir',
                    null,
                    null,
                    new DateTime(),
                    null,
                    null,
                    FileStatus::NEW->value,
                    null,
                    $entityManager->getRepository(Account::class)->getAccount($this->account)
                ));

            $this->core->logger->info('CREATE_DIR | '.' dir: '.$path.'\\'.$name.
                ' | controller: '.$this->account->getCloud().
                ' | user:' . $this->account->getUser());

            return $this->drive($doctrine,$session,$request,$cloud);

        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getStatusCode());
        }
    }

    /**
     *
     * Crea un fichero con el nombre y en la ruta especificados
     *
     * @Route("/{cloud}/drive/createFile", name="createFile", methods={"POST"})
     */
    public function createFile(ManagerRegistry $doctrine, SessionInterface $session, Request $request, string $cloud): Response
    {
        try{
            $entityManager = $doctrine->getManager();

            $name=$request->get('name');
            $path=$request->get('path');

            $this->createContext($cloud);
            $this->retriveCore($session,$request);
            $this->core->createFile($path,$name);

            $entityManager->getRepository(Metadata::class)->store(
                new Metadata($name,
                    null,
                    $path,
                    null,
                    'file',
                    0,
                    pathinfo($name, PATHINFO_EXTENSION),
                    new DateTime(),
                    null,
                    null,
                    FileStatus::NEW->value,
                    null,$entityManager->getRepository(Account::class)->getAccount($this->account)
                ));

            $this->core->logger->info('CREATE_FILE | '.' file: '.$path.'\\'.$name.
                ' | controller: '.$this->account->getCloud().
                ' | user:' . $this->account->getUser());

            return $this->drive($doctrine,$session,$request,$cloud);

        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getStatusCode());
        }
    }

    /**
     *
     * Elimina el archivo que se encuentre en la ruta especificada
     *
     * @Route("/{cloud}/drive/delete", name="delete", methods={"DELETE"})
     */
    public function delete(ManagerRegistry $doctrine, SessionInterface $session, Request $request, string $cloud): Response
    {
        try {

            $entityManager = $doctrine->getManager();

            $this->createContext($cloud);
            $this->retriveCore($session,$request);

            $path=$request->get('path');
            $name=$request->get('name');

            $fullPath=$path.'/'.$name;

            $account = $entityManager->getRepository(Account::class)->getAccount($this->account);
            $archivo=$this->core->getArchivo(str_replace('\\', '/', ($fullPath)));

            /* --- Se obtiene y configura los metadatadatos del archivo. Si no existen registros
                   previos se crean y si existen se modifican --- */

            $metadata = $this->core->getBasicMetadata($archivo,$account);

            $metadata->setName(basename($fullPath));
            $metadata->setPath(dirname($fullPath));
            $metadata->setStatus(FileStatus::DELETED->value);

            $entityManager->getRepository(Metadata::class)->store($metadata);
            $entityManager->getRepository(Metadata::class)->deleteDirectory($metadata);

            $this->core->delete($fullPath);

            $this->core->logger->info('DELETE | '.' file: '.$fullPath.
                ' | controller: '.$this->account->getCloud().
                ' | user:' . $this->account->getUser());

            return $this->drive($doctrine,$session,$request,$cloud);

        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getStatusCode());
        }
    }

    /**
     *
     * Envia al cliente el archivo en crudo solicitado en la ruta
     *
     * @Route("/{cloud}/drive/upload", name="upload", methods={"POST"})
     */
    public function upload(ManagerRegistry $doctrine, SessionInterface $session, Request $request, string $cloud): Response
    {
        try {
            $entityManager = $doctrine->getManager();

            $this->createContext($cloud);
            $this->retriveCore($session,$request);

// Se obtiene el contenido del fichero en forma UploadedFile
            $content=$this->core->getUploadedFile($request->files->get('content'));

            $sourcePath=$content->getPathname();
            $destinationPath=$request->get('path');

            $this->core->upload($destinationPath,$content);

// Se distingue entre colocar el archivo en root o en un directorio
            if(!empty($destinationPath))
            {
                $uploadPath=$destinationPath.'\\'.$content->getClientOriginalName();
            } else{
                $uploadPath=$content->getClientOriginalName();
            }

            $archivo=$this->core->getArchivo(str_replace('\\', '/', ($uploadPath)));

            $metadata = $this->core->getBasicMetadata($archivo,$entityManager->getRepository(Account::class)->getAccount($this->account));

            $metadata->setName($content->getClientOriginalName());
            $metadata->setPath($destinationPath);
            $metadata->setStatus(FileStatus::NEW->value);

            $entityManager->getRepository(Metadata::class)->store($metadata);

            $this->core->logger->info('UPLOAD | '.' file: '.$destinationPath.'\\'.$content->getClientOriginalName().
                ' | controller: '.$this->account->getCloud().
                ' | user:' . $this->account->getUser());

            return $this->drive($doctrine,$session,$request,$cloud);
        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getStatusCode());
        }
    }

    /**
     *
     * Envia los datos y metadatos del archivo especificado en la ruta
     *
     * @Route("/{cloud}/drive/getArchive", name="getArchive", methods={"GET"})
     */
    public function getArchive(ManagerRegistry $doctrine, SessionInterface $session, Request $request, string $cloud): Response
    {
        try {
            $entityManager = $doctrine->getManager();

            $this->createContext($cloud);
            $this->retriveCore($session,$request);
            $path=$request->get('path');

            $account = $entityManager->getRepository(Account::class)->getAccount($this->account);

            $fileMetadata=$entityManager->getRepository(Metadata::class)->findByExactPathAndAccountNull($account,dirname($path),basename($path));

            $file=$this->core->getAnArchive($path);
            if ($fileMetadata)
            {
                $file['visibility'] = $fileMetadata->getVisibility()??$file['visibility'];
                $file['extra_metadata']['author'] = $fileMetadata->getAuthor();
                $file['extra_metadata']['extra'] = $fileMetadata->getExtra();
            }

           /* $this->core->logger->info('GET_ARCHIVE | '.' archive: '.$path.
                ' | controller: '.$this->account->getCloud().
                ' | user:' . $this->account->getUser()); */

            return new JsonResponse($file,Response::HTTP_OK);
        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getStatusCode());
        }

    }

    /**
     *
     * Guarda los nuevos metadatos de un archvio en base de datos
     *
     * @Route("/{cloud}/drive/editMetadata", name="editMetadata", methods={"PUT","PATCH"})
     */
    public function editMetadata(ManagerRegistry $doctrine, SessionInterface $session, Request $request, string $cloud): Response
    {
        try {
            $entityManager = $doctrine->getManager();

            $this->createContext($cloud);
            $this->retriveCore($session,$request);
            $path=$request->get('path');

            $account = $entityManager->getRepository(Account::class)->getAccount($this->account);

            $metadata=json_decode($request->get('metadata'),true);

            $file=$entityManager->getRepository(Metadata::class)->findByExactPathAndAccountNull($account,dirname($path),basename($path));
            if($file)
            {
                $file->setAuthor($metadata['author']);
                $file->setVisibility($metadata['visibility']);
                $file->setExtra(json_encode($metadata['extra']));
                $file->setStatus(FileStatus::MODIFIED->value);
            } else //Si $file no existe es probable que sea una primera modificación de un fichero no indexado
            {
                $fileMetadata=$this->core->getAnArchive($path);
                $file = new Metadata(
                    basename($fileMetadata['path']),
                    $fileMetadata['extra_metadata']['id']??null,
                    dirname($this->core->cleanOwncloudPath($fileMetadata['path'])),
                    $fileMetadata['extra_metadata']['virtual_path']??null,
                    $fileMetadata['type'],
                    $fileMetadata['file_size']??null,
                    $fileMetadata['mime_type']??null,
                    (new DateTime())->setTimestamp($fileMetadata['last_modified']),
                    $metadata['author']??null,
                    $metadata['visibility']??$fileMetadata['visibility'],
                    FileStatus::MODIFIED->value,
                    json_encode($metadata['extra']??null),
                    $account);
            }

            $entityManager->getRepository(Metadata::class)->store($file);

            $this->core->logger->info('EDIT_METADATA | '.' archive: '.$path.
                ' | controller: '.$this->account->getCloud().
                ' | user:' . $this->account->getUser());

            return new JsonResponse('Metadatos editados correctamente',Response::HTTP_OK);
        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getStatusCode());
        }
    }

    /**
     *
     * Copia los ficheros de un filesystem a otro
     *
     * @Route("/{cloud}/copy", name="copy", methods={"POST"})
     */
    public function copy(ManagerRegistry $doctrine, SessionInterface $session, Request $request, string $cloud): Response
    {
        try {
            $entityManager = $doctrine->getManager();

            $sourceFullPath=$request->get('sourcePath'); // aa/a.txt
            $destinationDirectoryPath=$request->get('destinationPath'); // algun lugar/aa/
            $destinationFullPath=$destinationDirectoryPath.'/'.basename($sourceFullPath); // algun lugar/aa/a.txt
            $destinationCloud=$request->get('destinationCloud');

//Obtenemos las cuentas y filesystem que vamos a usar
            $filesSystems=$this->retriveMultiFileSystem($session,$request,$cloud,$destinationCloud);

            $this->core->copyWithMetadata($filesSystems,$entityManager,$sourceFullPath,$destinationDirectoryPath,$destinationFullPath);

            $this->core->logger->info('COPY | origen: '.$cloud.'::'.$sourceFullPath.' | destination: '.$destinationCloud.'::'.$destinationFullPath);

            return new JsonResponse('Ok',Response::HTTP_OK );
        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(), response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}