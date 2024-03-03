<?php

namespace UEMC\Core\Controller;

use PHPUnit\Util\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

use UEMC\Core\Entity\Account;
use UEMC\Core\Service\CloudService as Core;
use UEMC\OwnCloud\Service\CloudService as OwnCloudCore;
use UEMC\Ftp\Service\CloudService as FtpCore;
use UEMC\GoogleDrive\Service\CloudService as GoogleDriveCore;
use UEMC\OneDrive\Service\CloudService as OneDriveCore;


class AbstractCoreController extends AbstractController
{

    private Account $account;
    private Core $core;

    private function createContext(string $cloud): void
    {
        switch ($cloud) {
            case 'onedrive':
                $this->core=new OneDriveCore();
                break;
            case 'googledrive':
                $this->core=new GoogleDriveCore();
                break;
            case 'owncloud':
                $this->core=new OwnCloudCore();
                break;
            case 'ftp':
                $this->core=new FtpCore();
                break;
            default:
                throw new Exception('Error de controlador');
        }
        $this->account=new Account();
    }

    private function retriveCore(SessionInterface $session, Request $request): void
    {
        $ruta=$request->attributes->get('_route');
        $accountId = $request->query->get('accountId') ?? $request->request->get('accountId') ?? null;

        if($session->has('accounts') and $ruta !== 'login' )
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

        $this->createContext($cloud);
        $this->retriveCore($session,$request);

        $this->core->login($session,$request);
        return $this->redirectToRoute('_home_index');
    }

    /**
     * @Route("/{cloud}/logout", name="logout", methods={"POST"})
     */
    public function logout(SessionInterface $session, Request $request, string $cloud): Response
    {
        $this->createContext($cloud);
        $this->retriveCore($session,$request);

        return $this->redirectToRoute('_home_index',['status' => $this->core->logout($session,$request)]);
    }

    /**
     * @Route("/{cloud}/drive", name="drive")
     */
    public function drive(SessionInterface $session, Request $request, string $cloud): Response
    {
        $this->createContext($cloud);
        $this->retriveCore($session,$request);

        return $this->json($this->core->listDirectory($request->get('path')));
    }

    /**
     * @Route("/{cloud}/drive/download", name="download")
     */
    public function download(SessionInterface $session, Request $request, string $cloud): Response
    {
        $this->createContext($cloud);
        $this->retriveCore($session,$request);

        return $this->core->download($request->get('path'),$request->get('name'));
    }

    /**
     * @Route("/{cloud}/drive/createDir", name="createDir")
     */
    public function createDir(SessionInterface $session, Request $request, string $cloud): Response
    {
        $this->createContext($cloud);
        $this->retriveCore($session,$request);

        return $this->json($this->core->createDir($request->get('path'),$request->get('name')));
    }

    /**
     * @Route("/{cloud}/drive/createFile", name="createFile")
     */
    public function createFile(SessionInterface $session, Request $request, string $cloud): Response
    {
        $this->createContext($cloud);
        $this->retriveCore($session,$request);

        return $this->json($this->core->createFile($request->get('path'),$request->get('name')));
    }

    /**
     * @Route("/{cloud}/drive/delete", name="delete")
     */
    public function delete(SessionInterface $session, Request $request, string $cloud): Response
    {
        $this->createContext($cloud);
        $this->retriveCore($session,$request);

        return $this->json($this->core->delete($request->get('path')));
    }

    /**
     * @Route("/{cloud}/drive/upload", name="upload")
     */
    public function upload(SessionInterface $session, Request $request, string $cloud): Response
    {
        $this->createContext($cloud);
        $this->retriveCore($session,$request);

        return $this->json($this->core->upload($request->get('path'),$request->files->get('content')));
    }
}