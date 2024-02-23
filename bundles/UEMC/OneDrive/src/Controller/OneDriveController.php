<?php

namespace UEMC\OneDrive\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use UEMC\OneDrive\Service\CloudService;



class OneDriveController extends AbstractController
{

    /**
     * @Route("/onedrive", name="onedrive_index")
     */
    public function index(): Response
    {
        return $this->render('@UEMCOneDriveBundle/index.html.twig', [
            'test' => "Todo bien"
        ]);
    }

    /**
     * @Route("/onedrive/access", name="onedrive_access")
     */
    public function access(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        $tokenRespones=$cloud->token($session,$request);
        $userResponse=$cloud->getUserInfo($session,$request);
        return $this->redirectToRoute('_home_index', [
            'status' => $tokenRespones.$userResponse
        ]);
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
     * @Route("/onedrive/user", name="onedrive_user")
     */
    public function user_info(SessionInterface $session, Request $request, CloudService $cloud): Response
    {

        return $this->render('@UEMCOneDriveBundle/index.html.twig', [
            'test' => $cloud->getUserInfo($session,$request)
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
