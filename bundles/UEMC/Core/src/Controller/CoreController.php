<?php

namespace UEMC\Core\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Exception;
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
                $accountExists=$entityManager->getRepository(Account::class)->getAccount($account);
            } else
            {
                $entityManager->getRepository(Account::class)->updateAcount($account);
            }

            $account->setId($accountExists->getId());
            $accountId=$this->core->setSession($session,$account);

        }catch (CloudException | Exception $e)
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
        }catch (CloudException |Exception $e)
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
        }catch (CloudException |Exception $e)
        {
            return new JsonResponse($e->getMessage(),$e->getStatusCode());
        }
    }

    /**
     * @Route("/{cloud}/drive", name="drive")
     */
    public function drive(SessionInterface $session, Request $request, string $cloud): Response
    {
        try {
            $this->createContext($cloud);
            $this->retriveCore($session,$request);

            return new JsonResponse($this->core->listDirectory($request->get('path')));
        }catch (CloudException |Exception $e)
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
        }catch (CloudException |Exception $e)
        {
            return new JsonResponse($e->getMessage(),$e->getStatusCode());
        }
    }

    /**
     * @Route("/{cloud}/drive/createDir", name="createDir")
     */
    public function createDir(SessionInterface $session, Request $request, string $cloud): Response
    {
        try {
            $this->createContext($cloud);
            $this->retriveCore($session,$request);
            $this->core->createDir($request->get('path'),$request->get('name'));
            return new JsonResponse();
        }catch (CloudException |Exception $e)
        {
            return new JsonResponse($e->getMessage(),$e->getStatusCode());
        }
    }

    /**
     * @Route("/{cloud}/drive/createFile", name="createFile")
     */
    public function createFile(SessionInterface $session, Request $request, string $cloud): Response
    {
        try{
            $this->createContext($cloud);
            $this->retriveCore($session,$request);
            $this->core->createFile($request->get('path'),$request->get('name'));
            return new JsonResponse();
        }catch (CloudException |Exception $e)
        {
            return new JsonResponse($e->getMessage(),$e->getStatusCode());
        }
    }

    /**
     * @Route("/{cloud}/drive/delete", name="delete")
     */
    public function delete(SessionInterface $session, Request $request, string $cloud): Response
    {
        try {
            $this->createContext($cloud);
            $this->retriveCore($session,$request);
            $this->core->delete($request->get('path'));
            return new JsonResponse();
        }catch (CloudException |Exception $e)
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

            $nativeMetadata=$this->core->getNativeMetadata(str_replace('\\', '/', ($destinationPath.'\\'.$content->getClientOriginalName())));
            $nativeMetadata->setAccount($entityManager->getRepository(Account::class)->getAccount($this->account));
            $nativeMetadata->setName($content->getClientOriginalName());
            $nativeMetadata->setPath($destinationPath);
            $nativeMetadata->setStatus(FileStatus::NEW->value);

            $entityManager->getRepository(Metadata::class)->upload($nativeMetadata);

            return new JsonResponse(null,Response::HTTP_OK);
        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getStatusCode());
        }
    }
}