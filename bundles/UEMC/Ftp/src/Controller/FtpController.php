<?php

namespace UEMC\Ftp\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

use UEMC\Ftp\Entity\FtpAccount;
use UEMC\Ftp\Service\CloudService as FtpCore;

class FtpController extends AbstractController
{

    private FtpCore $ftpCore;
    private FtpAccount $ftpAccount;

    public function __construct(RequestStack $requestStack)
    {
        $request=$requestStack->getCurrentRequest();

        $session=$request->getSession();

        $ruta=$request->attributes->get('_route');
        $accountId = $request->query->get('accountId') ?? $request->request->get('accountId');

        $this->ftpCore=new FtpCore();
        $this->ftpAccount=new FtpAccount();

        $this->ftpCore->loggerUEMC->debug($ruta.$accountId);

        if($session->has('ftpAccounts') and
            ($ruta !== 'ftp_login' and $ruta !== 'ftp_loginPOST'))
        {
            $this->ftpAccount=$this->ftpAccount->arrayToObject($session->get('ftpAccounts')[$accountId]);
            $filesystem=$this->ftpCore->constructFilesystem($this->ftpAccount);
            $this->ftpCore->setFilesystem($filesystem);
        }
    }

    /**
     * @Route("/ftp/drive", name="ftp_listDirecories")
     */
    public function listDirecories(Request $request): Response
    {
        return $this->json($this->ftpCore->listDirectory($request->get('path')));
    }

    /**
     * @Route("/ftp/drive/download", name="ftp_download")
     */
    public function download(Request $request): Response
    {
        return $this->ftpCore->download($request->get('path'),$request->get('name'));
    }

    /**
     * @Route("/ftp/drive/createDir", name="ftp_createDir")
     */
    public function createDir(Request $request): Response
    {
        return $this->json($this->ftpCore->createDir($request->get('path'),$request->get('name')));
    }

    /**
     * @Route("/ftp/drive/createFile", name="ftp_createFile")
     */
    public function createFile(Request $request): Response
    {
        return $this->json($this->ftpCore->createFile($request->get('path'),$request->get('name')));
    }

    /**
     * @Route("/ftp/drive/delete", name="ftp_delete")
     */
    public function delete(Request $request): Response
    {
        return $this->ftpCore->download($request->get('path'),$request->get('name'));
    }

    /**
     * @Route("/ftp/drive/upload", name="ftp_upload")
     */
    public function upload(Request $request): Response
    {
        return $this->json($this->ftpCore->upload($request->get('path'),$request->files->get('content')));
    }


    /**
     * @Route("/ftp/login", name="ftp_login", methods={"GET"})
     */
    public function loginGET(): Response
    {
        return $this->render('@UEMCFtpBundle/login.html.twig', [
            'test' => 'OK'
        ]);
    }

    /**
     * @Route("/ftp/login", name="ftp_loginPOST", methods={"POST"})
     */
    public function loginPOST(SessionInterface $session, Request $request): Response
    {
        if($this->ftpAccount->login($session,$request))
        {
            return $this->redirectToRoute('_home_index');
        } else
        {
            return $this->render('@UEMCFtpBundle/login.html.twig',['test' => 'KO']);
        }
    }

    /**
     * @Route("/ftp/logout", name="ftp_logout")
     */
    public function logout(SessionInterface $session, Request $request): Response
    {
        return $this->redirectToRoute('_home_index',['status' => $this->ftpAccount->logout($session,$request)]);
    }
}
