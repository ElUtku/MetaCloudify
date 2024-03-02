<?php

namespace UEMC\GoogleDrive\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use UEMC\GoogleDrive\Entity\GoogleDriveAccount;
use UEMC\GoogleDrive\Service\CloudService as GoogleDriveCore;
use Symfony\Component\HttpFoundation\Session\SessionInterface;



class GoogleDriveController extends AbstractController
{

    private GoogleDriveAccount $googleDriveAccount;
    private GoogleDriveCore $googleDriveCore;

    public function __construct(RequestStack $requestStack)
    {
        $request=$requestStack->getCurrentRequest();

        $session=$request->getSession();

        //$session->remove('googledriveAccounts');
        $this->googleDriveCore=new GoogleDriveCore();
        $this->googleDriveAccount=new GoogleDriveAccount();

        $ruta=$request->attributes->get('_route');
        $id = $request->query->get('id') ?? $request->request->get('id') ?? null;

        $this->googleDriveCore->loggerUEMC->debug('Controller: '.$ruta. ' y el id : '.$id);
        if($session->has('googledriveAccounts') and $ruta !== 'googledrive_login' )
        {
            $this->googleDriveAccount=$this->googleDriveAccount->arrayToObject($session->get('googledriveAccounts')[$id]);
            $filesystem=$this->googleDriveCore->constructFilesystem($this->googleDriveAccount);
            $this->googleDriveCore->setFilesystem($filesystem);
        }
    }

    /**
     * @Route("/googledrive/login", name="googledrive_login", methods={"GET"})
     */
    public function login(SessionInterface $session, Request $request): Response
    {
        $this->googleDriveAccount->login($session,$request);
        $this->googleDriveCore->loggerUEMC->debug('5');
        return $this->redirectToRoute('_home_index');
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

    /**
     * @Route("/googledrive/logout", name="googledrive_logout")
     */
    public function logout(SessionInterface $session, Request $request): Response
    {
        return $this->redirectToRoute('_home_index',['status' => $this->googleDriveAccount->logout($session,$request)]);

    }
}
