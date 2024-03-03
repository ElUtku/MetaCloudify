<?php

namespace UEMC\OneDrive\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use UEMC\Core\Entity\Account;
use UEMC\OneDrive\Service\CloudService as OneDriveCore;



class OneDriveController extends AbstractController
{

    private Account $account;
    private OneDriveCore $oneDriveCore;

    public function __construct(RequestStack $requestStack)
    {
        $request=$requestStack->getCurrentRequest();

        $session=$request->getSession();
        
        $this->oneDriveCore=new OneDriveCore();
        $this->account=new Account();

        $ruta=$request->attributes->get('_route');
        $accountId = $request->query->get('accountId') ?? $request->request->get('accountId') ?? null;

        $this->oneDriveCore->loggerUEMC->debug('Controller: '.$ruta. ' y el accountId : '.$accountId);
        if($session->has('onedriveAccounts') and $ruta !== 'onedrive_login' )
        {
            $this->account=$this->oneDriveCore->arrayToObject($session->get('onedriveAccounts')[$accountId]);
            $filesystem=$this->oneDriveCore->constructFilesystem($this->account);
            $this->oneDriveCore->setFilesystem($filesystem);
        }
    }

    /**
     * @Route("/onedrive/login", name="onedrive_login")
     */
    public function login(SessionInterface $session, Request $request): Response
    {
        $this->oneDriveCore->login($session,$request);
        return $this->redirectToRoute('_home_index');
    }

    /**
     * @Route("/onedrive/logout", name="onedrive_logout", methods={"POST"})
     */
    public function logout(SessionInterface $session, Request $request): Response
    {
        return $this->redirectToRoute('_home_index',['status' => $this->oneDriveCore->logout($session,$request)]);
    }

    /**
     * @Route("/onedrive/drive", name="onedrive_drive")
     */
    public function drive(Request $request): Response
    {
        return $this->json($this->oneDriveCore->listDirectory($request->get('path')));
    }

    /**
     * @Route("/onedrive/drive/download", name="onedrive_download")
     */
    public function download(Request $request): Response
    {
        return $this->oneDriveCore->download($request->get('path'),$request->get('name'));
    }

    /**
     * @Route("/onedrive/drive/createDir", name="onedrive_createDir")
     */
    public function createDir(Request $request): Response
    {
        return $this->json($this->oneDriveCore->createDir($request->get('path'),$request->get('name')));
    }

    /**
     * @Route("/onedrive/drive/createFile", name="onedrive_createFile")
     */
    public function createFile(Request $request): Response
    {
        return $this->json($this->oneDriveCore->createFile($request->get('path'),$request->get('name')));
    }

    /**
     * @Route("/onedrive/drive/delete", name="onedrive_delete")
     */
    public function delete(Request $request): Response
    {
        return $this->json($this->oneDriveCore->delete($request->get('path')));
    }

    /**
     * @Route("/onedrive/drive/upload", name="onedrive_upload")
     */
    public function upload(Request $request): Response
    {
        return $this->json($this->oneDriveCore->upload($request->get('path'),$request->files->get('content')));
    }
}
