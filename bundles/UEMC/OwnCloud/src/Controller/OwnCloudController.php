<?php

namespace UEMC\OwnCloud\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use UEMC\Core\Entity\Account;
use UEMC\OwnCloud\Service\CloudService as OwncloudCore;


class OwnCloudController extends AbstractController
{
    private OwncloudCore $owncloudCore;
    private Account $account;

    public function __construct(RequestStack $requestStack)
    {
        $request=$requestStack->getCurrentRequest();

        $session=$request->getSession();

        $ruta=$request->attributes->get('_route');
        $accountId = $request->query->get('accountId') ?? $request->request->get('accountId');

        //$session->remove('owncloudAccounts');
        $this->owncloudCore=new OwncloudCore();
        $this->account=new Account();

        $this->owncloudCore->loggerUEMC->debug($ruta.$accountId);

        if($session->has('owncloudAccounts') and
            ($ruta !== 'owncloud_login' and $ruta !== 'owncloud_loginPOST'))
        {
            $this->account=$this->owncloudCore->arrayToObject($session->get('owncloudAccounts')[$accountId]);
            $filesystem=$this->owncloudCore->constructFilesystem($this->account);
            $this->owncloudCore->setFilesystem($filesystem);
        }
    }

    /**
     * @Route("/owncloud/login", name="owncloud_login", methods={"GET"})
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
        if ($this->owncloudCore->login($session, $request)) {
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
        return $this->redirectToRoute('_home_index',['status' => $this->account->logout($session,$request)]);
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
}
