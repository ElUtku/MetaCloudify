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
        if($session->has('googledriveAccounts') and $ruta !== 'GoogleDrive_login' )
        {
            $this->googleDriveAccount=$this->googleDriveAccount->arrayToObject($session->get('googledriveAccounts')[$id]);
            $filesystem=$this->googleDriveCore->constructFilesystem($this->googleDriveAccount);
            $this->googleDriveCore->setFilesystem($filesystem);
        }
    }

    /**
     * @Route("/GoogleDrive/login", name="GoogleDrive_login", methods={"GET"})
     */
    public function login(SessionInterface $session, Request $request): Response
    {
        $this->googleDriveAccount->login($session,$request);
        $this->googleDriveCore->loggerUEMC->debug('5');
        return $this->redirectToRoute('_home_index');
    }

    /**
     * @Route("/GoogleDrive/drive", name="GoogleDrive_drive")
     */
    public function drive(Request $request): Response
    {
        return $this->json($this->googleDriveCore->listDirectory($request->get('path')));
    }

    /**
     * @Route("/GoogleDrive/drive/download", name="GoogleDrive_download")
     */
    public function download(Request $request): Response
    {
        return $this->json($this->googleDriveCore->download($request->get('path'),$request->get('name')));
    }

    /**
     * @Route("/GoogleDrive/drive/createDir", name="GoogleDrive_createDir")
     */
    public function createDir(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        return $this->json($this->googleDriveCore->createDir($request->get('path'),$request->get('name')));
    }

    /**
     * @Route("/GoogleDrive/drive/createFile", name="GoogleDrive_createFile")
     */
    public function createFile(Request $request): Response
    {
        return $this->json($this->googleDriveCore->createFile($request->get('path'),$request->get('name')));
    }

    /**
     * @Route("/GoogleDrive/drive/delete", name="GoogleDrive_delete")
     */
    public function delete(Request $request): Response
    {
        return $this->json($this->googleDriveCore->delete($request->get('path')));
    }

    /**
     * @Route("/GoogleDrive/drive/upload", name="GoogleDrive_upload")
     */
    public function upload(Request $request): Response
    {
        return $this->json($this->googleDriveCore->upload($request->get('path'),$request->files->get('content')));
    }

    /**
     * @Route("/GoogleDrive/logout", name="GoogleDrive_logout", methods={"GET"})
     */
    public function logout(SessionInterface $session, Request $request): Response
    {
        return $this->redirectToRoute('_home_index',['status' => $this->googleDriveAccount->logout($session,$request)]);

    }
}
