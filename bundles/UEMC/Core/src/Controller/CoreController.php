<?php

namespace UEMC\Core\Controller;

use DateTime;
use Doctrine\Persistence\ManagerRegistry;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

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
        $accountId = $request->query->get('accountId') ?? $request->request->get('accountId') ?? null;

        if($session->has('accounts') and $ruta !== 'login' and $ruta !== 'login_token' and $ruta !== 'loginWeb' )
        {
            $this->account = $this->core->arrayToObject($session->get('accounts')[$accountId]);
            $filesystem = $this->core->constructFilesystem($this->account);
            $this->core->setFilesystem($filesystem);
        }
    }

    /**
     *
     * Login generico para autenticarse via web.
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

            $accountExists=$entityManager->getRepository(Account::class)->loggin($account);

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
     * Este login debe ser usado como endpoint para obtener el identificador de la cuenta en la sesion.
     *
     * @Route("/{cloud}/login/token", name="login_token", methods={"POST"})
     */
    public function loginPost(ManagerRegistry $doctrine, SessionInterface $session, Request $request, string $cloud): Response
    {
        try {
            $entityManager = $doctrine->getManager();

            $this->createContext($cloud);
            $this->retriveCore($session,$request);

            $account=$this->core->loginPost($session,$request);

            $accountExists=$entityManager->getRepository(Account::class)->loggin($account);

            $account->setId($accountExists->getId());
            $accountId=$this->core->setSession($session,$account);

            $this->core->logger->info('LOGGIN | '.'AccountId:'.$accountId.' | id: '.
                $accountExists->getId().' | controller: '.$account->getCloud().
                ' | user:' . $account->getUser());
            return new JsonResponse('El identificador es ' .$accountId);
        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getStatusCode());
        }
    }

    /**
     *
     * Este login proporciona una interfaz web para aquellas nubes que necesiten autentitcacion básica
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

            $path=$request->get('path');
            $contentInDirectory=$this->core->listDirectory($path);
            $contentInDirectoryArray=$contentInDirectory->toArray();

            $account = $entityManager->getRepository(Account::class)->getAccount($this->account);

            foreach ($contentInDirectoryArray as $archive)
            {
                $item=json_decode(json_encode($archive),true); //Se convierte a un objeto modificable
                $path=$item['path'];

                if ($entityManager->getRepository(Metadata::class)->findByExactPathAndAccountNull($account,dirname($path),basename($path)))
                {

                    $archivoTipado=$this->core->getTypeOfArchive($archive); //Se define si es carpeta o fichero
                    $metadata = $this->core->getMetadata($archivoTipado,$account);

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

            return new JsonResponse($archivesWhitMetadata??$contentInDirectoryArray);

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

            return $this->core->download($path,$name);
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

            $entityManager->getRepository(Metadata::class)->store(new Metadata($name,null,$path,null,'dir',null,null,new DateTime(),null,null,FileStatus::NEW->value,null,$entityManager->getRepository(Account::class)->getAccount($this->account)));

            $this->core->logger->info('CREATE_DIR | '.' dir: '.$path.'\\'.$name.
                ' | controller: '.$this->account->getCloud().
                ' | user:' . $this->account->getUser());


            return new JsonResponse();
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

            $entityManager->getRepository(Metadata::class)->store(new Metadata($name,null,$path,null,'file',0,pathinfo($name, PATHINFO_EXTENSION),new DateTime(),null,null,FileStatus::NEW->value,null,$entityManager->getRepository(Account::class)->getAccount($this->account)));

            $this->core->logger->info('CREATE_FILE | '.' file: '.$path.'\\'.$name.
                ' | controller: '.$this->account->getCloud().
                ' | user:' . $this->account->getUser());

            return new JsonResponse();
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

            $account = $entityManager->getRepository(Account::class)->getAccount($this->account);
            $archivo=$this->core->getArchivo(str_replace('\\', '/', ($path)));

            $metadata = $this->core->getMetadata($archivo,$account);

            $metadata->setName(basename($path));
            $metadata->setPath(dirname($path));
            $metadata->setStatus(FileStatus::DELETED->value);

            $entityManager->getRepository(Metadata::class)->store($metadata);
            $entityManager->getRepository(Metadata::class)->deleteDirectory($metadata);

            $this->core->delete($path);

            $this->core->logger->info('DELETE | '.' file: '.$path.
                ' | controller: '.$this->account->getCloud().
                ' | user:' . $this->account->getUser());

            return new JsonResponse();
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

            $content=$this->core->getUploadedFile($request->files->get('content'));

            $sourcePath=$content->getPathname();
            $destinationPath=$request->get('path');

            $this->core->upload($destinationPath,$content);

            if(!empty($destinationPath))
            {
                $uploadPath=$destinationPath.'\\'.$content->getClientOriginalName();
            } else{
                $uploadPath=$content->getClientOriginalName();
            }

            $archivo=$this->core->getArchivo(str_replace('\\', '/', ($uploadPath)));

            $metadata = $this->core->getMetadata($archivo,$entityManager->getRepository(Account::class)->getAccount($this->account));

            $metadata->setName($content->getClientOriginalName());
            $metadata->setPath($destinationPath);
            $metadata->setStatus(FileStatus::NEW->value);

            $entityManager->getRepository(Metadata::class)->store($metadata);

            $this->core->logger->info('UPLOAD | '.' file: '.$destinationPath.'\\'.$content->getClientOriginalName().
                ' | controller: '.$this->account->getCloud().
                ' | user:' . $this->account->getUser());

            return new JsonResponse(null,Response::HTTP_OK);
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

            $this->core->logger->info('GET_ARCHIVE | '.' archive: '.$path.
                ' | controller: '.$this->account->getCloud().
                ' | user:' . $this->account->getUser());

            return new JsonResponse($file);
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

            $account = $entityManager->getRepository(Account::class)->getAccount($this->account);

            $path=$request->get('path');
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
                dump($fileMetadata);
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

            return new JsonResponse(null,Response::HTTP_OK);
        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getStatusCode());
        }
    }
}