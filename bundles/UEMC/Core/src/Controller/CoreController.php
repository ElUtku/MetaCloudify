<?php

namespace UEMC\Core\Controller;

use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Microsoft\Graph\Model\File;
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
     *  Login generico para autenticarse via web.
      *
     * @Route("/{cloud}/login", name="login")
     */
    public function login(ManagerRegistry $doctrine, SessionInterface $session, Request $request, string $cloud): Response
    {

        try {
            $this->createContext($cloud);
            $this->retriveCore($session,$request);

            $account=$this->core->login($session,$request);

            $entityManager = $doctrine->getManager();
            $accountExists=$entityManager->getRepository(Account::class)->getAccount($account);
            if($accountExists==null)
            {
                $entityManager->getRepository(Account::class)->newAcount($account);
                $accountExists=$entityManager->getRepository(Account::class)->getAccount($account); //Una vez guardada la nuva cuenta se recupera
            } else
            {
                $accountExists->setLastSession($account->getLastSession());
                $accountExists->setLastIp($account->getLastIp());
                $entityManager->getRepository(Account::class)->updateAcount();
            }

            $account->setId($accountExists->getId());
            $accountId=$this->core->setSession($session,$account);

        }catch (CloudException $e)
        {
            $this->addFlash('error','CODE: '.$e->getStatusCode(). ' - MESSAGE: '.$e->getMessage());
        }
        return $this->redirectToRoute('_home_index');
    }

    /**
     *
     *  Este login debe ser usado como endpoint para obtener el identificador de la cuenta en la sesion.
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
            $accountId=$this->core->setSession($session,$account);

            $entityManager = $doctrine->getManager();
            $entityManager->getRepository(Account::class)->logAcount($account);

            return new JsonResponse('El identificador es ' .$accountId);
        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getStatusCode());
        }
    }

    /**
     *
     * Este login proporciona una interfaz web para aquiellas nubes que necesiten autentitcacion básica
     *
     * @Route("/{cloud}/login/web", name="login_web")
     * @param SessionInterface $session
     * @param Request $request
     * @param string $cloud
     * @return Response
     */
    public function loginWeb(SessionInterface $session, Request $request, string $cloud) : Response
    {
        return match ($cloud) {
            'owncloud' => $this->render('@UEMCOwnCloudBundle/login.html.twig'),
            'ftp' => $this->render('@UEMCFtpBundle/login.html.twig'),
            default => new JsonResponse(ErrorTypes::ERROR_INDETERMINADO->getErrorMessage(),
                ErrorTypes::ERROR_INDETERMINADO->getErrorCode()),
        };
    }

    /**
     * @Route("/{cloud}/logout", name="logout", methods={"POST"})
     */
    public function logout(SessionInterface $session, Request $request, string $cloud): Response
    {
        try {
            $this->createContext($cloud);
            $this->retriveCore($session,$request);
            $this->core->logout($session,$request);
            return new JsonResponse();
        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getStatusCode());
        }
    }

    /**
     * @Route("/{cloud}/drive", name="drive")
     */
    public function drive(ManagerRegistry $doctrine, SessionInterface $session, Request $request, string $cloud): Response
    {
        try {
            $entityManager = $doctrine->getManager();

            $this->createContext($cloud);
            $this->retriveCore($session,$request);

            $path=$request->get('path');
            $archives=$this->core->listDirectory($path);

            foreach ($archives as $archive)
            {
                $path=$archive['path'];
                $account = $entityManager->getRepository(Account::class)->getAccount($this->account);

                if ($entityManager->getRepository(Metadata::class)->findByExactPathAndAccountNull($account,dirname($path),basename($path)))
                {
                    $archivo=$this->core->getArchivo($path);
                    $metadata = $this->core->getMetadata($archivo,$account);

                    $extraMetadata=$entityManager->getRepository(Metadata::class)->getCloudMetadata($metadata);

                    $archive['extra_metadata'] = [
                        'virtual_name' => $extraMetadata->getVirtualName(),
                        'virtual_path' => $extraMetadata->getVirtualPath(),
                        'author' => $extraMetadata->getAuthor(),
                        'visibility' => $extraMetadata->getVisibility(),
                        'status' => $extraMetadata->getStatus(),
                        'extra' => $extraMetadata->getExtra(),
                    ];
                }

                $archivesWhitMetadata[]=$archive;
            }

            return new JsonResponse($archivesWhitMetadata??$archives);

        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getStatusCode());
        }

    }

    /**
     * @Route("/{cloud}/drive/download", name="download")
     */
    public function download(SessionInterface $session, Request $request, string $cloud): Response
    {
        try {
            $this->createContext($cloud);
            $this->retriveCore($session,$request);

            return $this->core->download($request->get('path'),$request->get('name'));
        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getStatusCode());
        }
    }

    /**
     * @Route("/{cloud}/drive/createDir", name="createDir")
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

            $entityManager->getRepository(Metadata::class)->store(new Metadata($name,null,$path,null,'dir',null,null,new \DateTime(),null,null,FileStatus::NEW->value,null,$entityManager->getRepository(Account::class)->getAccount($this->account)));
            return new JsonResponse();
        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getStatusCode());
        }
    }

    /**
     * @Route("/{cloud}/drive/createFile", name="createFile")
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

            $entityManager->getRepository(Metadata::class)->store(new Metadata($name,null,$path,null,'file',0,pathinfo($name, PATHINFO_EXTENSION),new \DateTime(),null,null,FileStatus::NEW->value,null,$entityManager->getRepository(Account::class)->getAccount($this->account)));

            return new JsonResponse();
        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getStatusCode());
        }
    }

    /**
     * @Route("/{cloud}/drive/delete", name="delete")
     */
    public function delete(ManagerRegistry $doctrine, SessionInterface $session, Request $request, string $cloud): Response
    {
        try {

            $entityManager = $doctrine->getManager();

            $this->createContext($cloud);
            $this->retriveCore($session,$request);

            $path=$request->get('path');

            $archivo=$this->core->getArchivo(str_replace('\\', '/', ($path)));

            $metadata = $this->core->getMetadata($archivo,$entityManager->getRepository(Account::class)->getAccount($this->account));

            $metadata->setName(basename($path));
            $metadata->setPath(dirname($path));
            $metadata->setStatus(FileStatus::DELETED->value);

            $entityManager->getRepository(Metadata::class)->store($metadata);
            $entityManager->getRepository(Metadata::class)->deleteDirectory($metadata);

            $this->core->delete($path);

            return new JsonResponse();
        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getStatusCode());
        }
    }

    /**
     * @Route("/{cloud}/drive/upload", name="upload")
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

            return new JsonResponse(null,Response::HTTP_OK);
        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getStatusCode());
        }
    }

    /**
     * @Route("/{cloud}/drive/getArchive", name="getArchive")
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
               $file['extra_metadata']['extra'] = $fileMetadata->getExtra();
               $file['extra_metadata']['author'] = $fileMetadata->getAuthor();
                $file['visibility'] = $fileMetadata->getVisibility();
            }
            return new JsonResponse($file);
        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getStatusCode());
        }

    }

    /**
     * @Route("/{cloud}/drive/editMetadata", name="editMetadata")
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
                $file = new Metadata(
                    basename($fileMetadata['path']),
                    $fileMetadata['extra_metadata']['virtual_name']??null,
                    dirname($this->core->cleanOwncloudPath($fileMetadata['path'])),
                    $metadata['virtual_path']??null,
                    $fileMetadata['type'],
                    $fileMetadata['file_size']??null,
                    $fileMetadata['mime_type']??null,
                    (new DateTime())->setTimestamp($fileMetadata['last_modified']),
                    $metadata['author']??null,
                    $metadata['visibility']??$fileMetadata['visibility'],
                    $metadata['status']??FileStatus::MODIFIED->value,
                    json_encode($metadata['extra']??null),
                    $account);
            }

            $entityManager->getRepository(Metadata::class)->store($file);
            return new JsonResponse(null,Response::HTTP_OK);
        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getStatusCode());
        }
    }
}