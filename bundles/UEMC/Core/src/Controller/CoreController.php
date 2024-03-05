<?php

namespace UEMC\Core\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

use UEMC\Core\Entity\Account;
use UEMC\Core\Resources\ErrorTypes;
use UEMC\Core\Service\CloudException;
use UEMC\Core\Service\CloudService as Core;
use UEMC\OwnCloud\Service\CloudService as OwnCloudCore;
use UEMC\Ftp\Service\CloudService as FtpCore;
use UEMC\GoogleDrive\Service\CloudService as GoogleDriveCore;
use UEMC\OneDrive\Service\CloudService as OneDriveCore;
use function PHPUnit\Framework\isEmpty;


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
     * @Route("/{cloud}/login", name="login")
     */
    public function login(SessionInterface $session, Request $request, string $cloud): Response
    {
        try {
            $this->createContext($cloud);
            $this->retriveCore($session,$request);
            $this->core->login($session,$request);

            return $this->redirectToRoute('_home_index');
        }catch (CloudException $e)
        {
            return new JsonResponse($e->getCode(), $e->getMessage());
        }
    }

    /**
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
     * @Route("/{cloud}/login/token", name="login_token", methods={"POST"})
     */
    public function loginPost(SessionInterface $session, Request $request, string $cloud): Response
    {
        try {
            $this->createContext($cloud);
            $this->retriveCore($session,$request);
            $accountId=$this->core->loginPost($session,$request);
            return new JsonResponse('El identificador es ' .$accountId);
        }catch (CloudException $e)
        {
            return new JsonResponse($e->getCode(), $e->getMessage());
        }
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
            return new JsonResponse($e->getCode(), $e->getMessage());
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
        }catch (CloudException $e)
        {
            return new JsonResponse($e->getCode(), $e->getMessage());
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
            return new JsonResponse($e->getCode(), $e->getMessage());
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
        }catch (CloudException $e)
        {
            return new JsonResponse($e->getCode(), $e->getMessage());
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
        }catch (CloudException $e)
        {
        return new JsonResponse($e->getCode(), $e->getMessage());
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
        }catch (CloudException $e)
        {
            return new JsonResponse($e->getCode(), $e->getMessage());
        }
    }

    /**
     * @Route("/{cloud}/drive/upload", name="upload")
     */
    public function upload(SessionInterface $session, Request $request, string $cloud): Response
    {
        try {
            $this->createContext($cloud);
            $this->retriveCore($session,$request);
            $this->core->upload($request->get('path'),$request->files->get('content'));
            return new JsonResponse();
        }catch (CloudException $e)
        {
            return new JsonResponse($e->getCode(), $e->getMessage());
        }
    }
}