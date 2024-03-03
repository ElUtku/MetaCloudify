<?php

namespace UEMC\GoogleDrive\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use UEMC\Core\Entity\Account;
use UEMC\GoogleDrive\Entity\GoogleDriveAccount;
use UEMC\GoogleDrive\Service\CloudService as GoogleDriveCore;
use Symfony\Component\HttpFoundation\Session\SessionInterface;



class GoogleDriveController extends AbstractController
{

    private Account $account;
    private GoogleDriveCore $googleDriveCore;

    public function __construct(RequestStack $requestStack)
    {
        $request=$requestStack->getCurrentRequest();

        $session=$request->getSession();

        //$session->remove('googledriveAccounts');
        $this->googleDriveCore=new GoogleDriveCore();
        $this->account=new Account();

        $ruta=$request->attributes->get('_route');
        $accountId = $request->query->get('accountId') ?? $request->request->get('accountId') ?? null;

        $this->googleDriveCore->loggerUEMC->debug('Controller: '.$ruta. ' y el id : '.$accountId);
        if($session->has('googledriveAccounts') and $ruta !== 'googledrive_login' )
        {
            $this->account=$this->googleDriveCore->arrayToObject($session->get('googledriveAccounts')[$accountId]);
            $filesystem=$this->googleDriveCore->constructFilesystem($this->account);
            $this->googleDriveCore->setFilesystem($filesystem);
        }
    }

    /**
     * @Route("/googledrive/login", name="googledrive_login", methods={"GET"})
     */
    public function login(SessionInterface $session, Request $request): Response
    {
        $this->googleDriveCore->login($session,$request);
        $this->googleDriveCore->loggerUEMC->debug('5');
        return $this->redirectToRoute('_home_index');
    }

    /**
     * @Route("/googledrive/logout", name="googledrive_logout")
     */
    public function logout(SessionInterface $session, Request $request): Response
    {
        return $this->redirectToRoute('_home_index',['status' => $this->googleDriveCore->logout($session,$request)]);

    }

    /**
     * @Route("/googledrive/drive", name="googledrive_drive")
     */
    public function drive(Request $request): Response
    {
        return $this->json($this->googleDriveCore->listDirectory($request->get('path')));
    }

    /**
     * @Route("/googledrive/drive/download", name="googledrive_download")
     */
    public function download(Request $request): Response
    {
        return $this->json($this->googleDriveCore->download($request->get('path'),$request->get('name')));
    }

    /**
     * @Route("/googledrive/drive/createDir", name="googledrive_createDir")
     */
    public function createDir(Request $request): Response
    {
        return $this->json($this->googleDriveCore->createDir($request->get('path'),$request->get('name')));
    }

    /**
     * @Route("/googledrive/drive/createFile", name="googledrive_createFile")
     */
    public function createFile(Request $request): Response
    {
        return $this->json($this->googleDriveCore->createFile($request->get('path'),$request->get('name')));
    }

    /**
     * @Route("/googledrive/drive/delete", name="googledrive_delete")
     */
    public function delete(Request $request): Response
    {
        return $this->json($this->googleDriveCore->delete($request->get('path')));
    }

    /**
     * @Route("/googledrive/drive/upload", name="googledrive_upload")
     */
    public function upload(Request $request): Response
    {
        return $this->json($this->googleDriveCore->upload($request->get('path'),$request->files->get('content')));
    }
}
