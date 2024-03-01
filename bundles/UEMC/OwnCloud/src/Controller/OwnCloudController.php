<?php

namespace UEMC\OwnCloud\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use UEMC\OwnCloud\Service\CloudService as OwncloudCore;
use UEMC\OwnCloud\Entity\OwnCloudAccount;


class OwnCloudController extends AbstractController
{
    private OwncloudCore $owncloudCore;
    private OwnCloudAccount $ownCloudAccount;

    public function __construct(RequestStack $requestStack)
    {
        $request=$requestStack->getCurrentRequest();

        $session=$request->getSession();

        $ruta=$request->attributes->get('_route');
        $id = $request->query->get('id') ?? $request->request->get('id');

        //$session->remove('owncloudAccounts');
        $this->owncloudCore=new OwncloudCore();
        $this->ownCloudAccount=new OwncloudAccount();

        $this->owncloudCore->loggerUEMC->debug($ruta.$id);

        if($session->has('owncloudAccounts') and
            ($ruta !== 'owncloud_loginGET' and $ruta !== 'owncloud_loginPOST'))
        {
            $this->ownCloudAccount=$this->ownCloudAccount->arrayToObject($session->get('owncloudAccounts')[$id]);
            $filesystem=$this->owncloudCore->constructFilesystem($this->ownCloudAccount);
            $this->owncloudCore->setFilesystem($filesystem);
        }
    }

    /**
     * @Route("/owncloud/drive", name="owncloud_drive")
     */
    public function drive(Request $request): Response
    {
        return $this->json($this->owncloudCore->listDirectory($request->get('path')));
    }

    /**
     * @Route("/owncloud/drive/download", name="owncloud_download")
     */
    public function download(Request $request): Response
    {
        return $this->owncloudCore->download($request->get('path'),$request->get('name'));
    }

    /**
     * @Route("/owncloud/drive/createDir", name="owncloud_createDir")
     */
    public function createDir(Request $request): Response
    {
        return $this->json($this->owncloudCore->createDir($request->get('path'),$request->get('name')));
    }

    /**
     * @Route("/owncloud/drive/createFile", name="owncloud_createFile")
     */
    public function createFile(Request $request): Response
    {
        return $this->json($this->owncloudCore->createFile($request->get('path'),$request->get('name')));
    }

    /**
     * @Route("/owncloud/drive/delete", name="owncloud_delete")
     */
    public function delete(Request $request): Response
    {
        return $this->json($this->owncloudCore->delete($request->get('path')));
    }

    /**
     * @Route("/owncloud/drive/upload", name="owncloud_upload")
     */
    public function upload(SessionInterface $session, Request $request): Response
    {
        return $this->json($this->owncloudCore->upload($request->get('path'),$request->files->get('content')));
    }

    /**
     * @Route("/owncloud/login", name="owncloud_loginGET", methods={"GET"})
     */
    public function loginGET(): Response
    {
        return $this->render('@UEMCOwnCloudBundle/login.html.twig', [
            'test' => 'OK'
        ]);
    }

    /**
     * @Route("/owncloud/login", name="owncloud_loginPOST", methods={"POST"})
     */
    public function loginPOST(SessionInterface $session, Request $request): Response
    {
        if ($this->ownCloudAccount->login($session, $request)) {
            return $this->redirectToRoute('_home_index');
        } else {
            return $this->render('@UEMCOwnCloudBundle/login.html.twig', ['test' => 'KO']);
        }
    }

    /**
     * @Route("/owncloud/logout", name="owncloud_logout")
     */
    public function logout(SessionInterface $session, Request $request): Response
    {
        return $this->redirectToRoute('_home_index',['status' => $this->ownCloudAccount->logout($session,$request)]);
    }
}
