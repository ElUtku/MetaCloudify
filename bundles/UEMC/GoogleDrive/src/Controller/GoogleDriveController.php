<?php

namespace UEMC\GoogleDrive\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use UEMC\GoogleDrive\Service\CloudService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;



class GoogleDriveController extends AbstractController
{

    /**
     * @Route("/GoogleDrive/", name="GoogleDrive_index", methods={"GET"})
     */
    public function index(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        return $this->render('@UEMCGoogleDriveBundle/index.html.twig', [
            'controller_name' => 'GoogleDriveController',
            'client_id' => $cloud->getClientID(),
            'redirect_uri_accesTap' => $cloud->getRedirectUriAccessTap(),
            'test' => "nada Por Aqui"
        ]);
    }

    /**
     * @Route("/GoogleDrive/login", name="GoogleDrive_login", methods={"GET"})
     */
    public function access(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        $cloud->auth($session,$request);
        return $this->redirectToRoute('_home_index');
    }

    /**
     * @Route("/GoogleDrive/accessTap", name="GoogleDrive_accessTap")
     */
    public function accessTap(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        $cloud->authTap($session,$request);
        return $this->redirectToRoute('GoogleDrive_index');
    }

    /**
     * @Route("/GoogleDrive/drive", name="GoogleDrive_drive")
     */
    public function drive(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        return $this->json($cloud->listDirectories($session,$request));
    }

    /**
     * @Route("/GoogleDrive/drive/download", name="GoogleDrive_download")
     */
    public function download(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        return $cloud->download($session,$request);
    }

    /**
     * @Route("/GoogleDrive/drive/createDir", name="GoogleDrive_createDir")
     */
    public function createDir(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        return $this->json($cloud->createDirectory($session,$request));
    }

    /**
     * @Route("/GoogleDrive/drive/createFile", name="GoogleDrive_createFile")
     */
    public function createFile(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        return $this->json($cloud->createFile($session,$request));
    }

    /**
     * @Route("/GoogleDrive/drive/delete", name="GoogleDrive_delete")
     */
    public function delete(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        return $this->json($cloud->delete($session,$request));
    }

    /**
     * @Route("/GoogleDrive/drive/upload", name="GoogleDrive_upload")
     */
    public function upload(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        return $this->json($cloud->upload($session,$request));
    }

    /**
     * @Route("/GoogleDrive/logout", name="GoogleDrive_logout", methods={"GET"})
     */
    public function logout(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        return $this->redirectToRoute('_home_index', [
            'status' => $cloud->logout($session,$request)
        ]);
    }
}
