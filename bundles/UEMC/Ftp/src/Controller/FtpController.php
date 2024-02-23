<?php

namespace UEMC\Ftp\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use UEMC\Ftp\Service\CloudService;

class FtpController extends AbstractController
{
    /**
     * @Route("/ftp", name="ftp_index")
     */
    public function index(CloudService $cloud): Response
    {
        return $this->render('@UEMCFtpBundle/index.html.twig', [
            'test' => 'OK'
        ]);
    }

    /**
     * @Route("/ftp/drive", name="ftp_listDirecories")
     */
    public function listDirecories(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        return $this->json($cloud->listDirecories($session,$request));
    }

    /**
     * @Route("/ftp/drive/download", name="ftp_download")
     */
    public function download(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        return $this->json($cloud->download($session,$request));
    }

    /**
     * @Route("/ftp/drive/createDir", name="ftp_createDir")
     */
    public function createDir(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        return $this->json($cloud->createDirectory($session,$request));
    }

    /**
     * @Route("/ftp/drive/createFile", name="ftp_createFile")
     */
    public function createFile(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        return $this->json($cloud->createFile($session,$request));
    }

    /**
     * @Route("/ftp/drive/delete", name="ftp_delete")
     */
    public function delete(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        return $this->json($cloud->delete($session,$request));
    }

    /**
     * @Route("/ftp/drive/upload", name="ftp_upload")
     */
    public function upload(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        return $this->json($cloud->upload($session,$request));
    }


    /**
     * @Route("/ftp/login", name="ftp_loginGET", methods={"GET"})
     */
    public function loginGET(CloudService $cloud): Response
    {
        return $this->render('@UEMCFtpBundle/login.html.twig', [
            'test' => 'OK'
        ]);
    }

    /**
     * @Route("/ftp/login", name="ftp_loginPOST", methods={"POST"})
     */
    public function loginPOST(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        if($cloud->login($session,$request))
        {
            return $this->redirectToRoute('_home_index');
        } else
        {
            return $this->render('@UEMCFtpBundle/login.html.twig',['test' => 'KO']);
        }
    }

    /**
     * @Route("/ftp/logout", name="ftp_logout", methods={"GET"})
     */
    public function logout(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        return $this->redirectToRoute('_home_index',['status' => $cloud->logout($session,$request)]);
    }
}
