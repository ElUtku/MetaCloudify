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

        //$this->oneDriveCore->loggerUEMC->debug('Controller: '.$ruta. ' y el id : '.$id);
        //$this->oneDriveCore->loggerUEMC->debug('Satete: '.$request->get('state'));
        $this->oneDriveCore->loggerUEMC->debug('1');
        if($session->has('onedriveAccounts') and $ruta !== 'onedrive_access' )
        {
            $this->oneDriveCore->loggerUEMC->debug('1.1');
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
        $tokenRespones=$this->oneDriveAccount->login($session,$request);
        //$this->oneDriveCore->loggerUEMC->debug('Error: '.$tokenRespones);
        $this->oneDriveCore->loggerUEMC->debug('5');
        return $this->redirectToRoute('_home_index');
    }

    /**
     * @Route("/onedrive/logout", name="onedrive_logout", methods={"GET"})
     */
    public function logout(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        return $this->redirectToRoute('_home_index', [
            'status' => $cloud->logout($session,$request)
        ]);
    }

    /**
     * @Route("/onedrive/drive", name="onedrive_drive")
     */
    public function drive(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        return $this->json($cloud->listDirectories($session,$request));
    }

    /**
     * @Route("/onedrive/drive/download", name="onedrive_download")
     */
    public function download(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        return $cloud->download($session,$request);
    }

    /**
     * @Route("/onedrive/drive/createDir", name="onedrive_createDir")
     */
    public function createDir(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        return $this->json($cloud->createDirectory($session,$request));
    }

    /**
     * @Route("/onedrive/drive/createFile", name="onedrive_createFile")
     */
    public function createFile(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        return $this->json($cloud->createFile($session,$request));
    }

    /**
     * @Route("/onedrive/drive/delete", name="onedrive_delete")
     */
    public function delete(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        return $this->json($cloud->delete($session,$request));
    }

    /**
     * @Route("/onedrive/drive/upload", name="onedrive_upload")
     */
    public function upload(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        return $this->json($cloud->upload($session,$request));
    }
}
