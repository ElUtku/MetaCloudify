<?php

namespace UEMC\OneDrive\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use UEMC\OneDrive\Entity\OneDriveAccount;
use UEMC\OneDrive\Service\CloudService as OneDriveCore;



class OneDriveController extends AbstractController
{

    private OneDriveAccount $oneDriveAccount;
    private OneDriveCore $oneDriveCore;

    public function __construct(RequestStack $requestStack)
    {
        $request=$requestStack->getCurrentRequest();

        $session=$request->getSession();

        //$session->remove('owncloudAccounts');
        $this->oneDriveCore=new OneDriveCore();
        $this->oneDriveAccount=new OneDriveAccount();

        $ruta=$request->attributes->get('_route');
        $id = $request->query->get('id') ?? $request->request->get('id') ?? null;

        $this->oneDriveCore->loggerUEMC->debug('Controller: '.$ruta. ' y el id : '.$id);
        if($session->has('onedriveAccounts') and $ruta !== 'onedrive_access' )
        {
            $this->oneDriveAccount=$this->oneDriveAccount->arrayToObject($session->get('onedriveAccounts')[$id]);
            $filesystem=$this->oneDriveCore->constructFilesystem($this->oneDriveAccount);
            $this->oneDriveCore->setFilesystem($filesystem);
        }
    }

    /**
     * @Route("/onedrive/access", name="onedrive_access")
     */
    public function access(SessionInterface $session, Request $request): Response
    {
        $this->oneDriveAccount->login($session,$request);
        $this->oneDriveCore->loggerUEMC->debug('5');
        return $this->redirectToRoute('_home_index');
    }

    /**
     * @Route("/onedrive/logout", name="onedrive_logout", methods={"GET"})
     */
    public function logout(SessionInterface $session, Request $request): Response
    {
        return $this->redirectToRoute('_home_index',['status' => $this->oneDriveAccount->logout($session,$request)]);
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
        return $this->json($this->oneDriveCore->download($request->get('path'),$request->get('name')));
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
