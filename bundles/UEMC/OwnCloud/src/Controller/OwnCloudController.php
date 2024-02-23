<?php

namespace UEMC\OwnCloud\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use UEMC\OwnCloud\Service\CloudService;

class OwnCloudController extends AbstractController
{
    /**
     * @Route("/owncloud/", name="owncloud_indexGET", methods={"GET"})
     */
    public function indexGET(CloudService $cloud): Response
    {
        return $this->render('@UEMCOwnCloudBundle/index.html.twig', [
            'test' => "OK"
        ]);
    }

    /**
     * @Route("/owncloud/drive", name="owncloud_drive")
     */
    public function drive(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        return $this->json($cloud->listDirectories($session, $request));
    }

    /**
     * @Route("/owncloud/drive/download", name="owncloud_download")
     */
    public function download(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        return $cloud->download($session, $request);
    }

    /**
     * @Route("/owncloud/drive/createDir", name="owncloud_createDir")
     */
    public function createDir(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        return $this->json($cloud->createDirectory($session,$request));
    }

    /**
     * @Route("/owncloud/drive/createFile", name="owncloud_createFile")
     */
    public function createFile(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        return $this->json($cloud->createFile($session,$request));
    }

    /**
     * @Route("/owncloud/drive/delete", name="owncloud_delete")
     */
    public function delete(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        return $this->json($cloud->delete($session,$request));
    }

    /**
     * @Route("/owncloud/drive/upload", name="owncloud_upload")
     */
    public function upload(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        return $this->json($cloud->upload($session,$request));
    }

    /**
     * @Route("/owncloud/login", name="owncloud_loginGET", methods={"GET"})
     */
    public function loginGET(CloudService $cloud): Response
    {
        return $this->render('@UEMCOwnCloudBundle/login.html.twig', [
            'test' => 'OK'
        ]);
    }

    /**
     * @Route("/owncloud/login", name="owncloud_loginPOST", methods={"POST"})
     */
    public function loginPOST(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        if($cloud->login($session,$request))
        {
            return $this->redirectToRoute('_home_index');
        } else
        {
            return $this->render('@UEMCOwnCloudBundle/login.html.twig',['test' => 'KO']);
        }
    }

    /**
     * @Route("/owncloud/logout", name="owncloud_logout", methods={"GET"})
     */
    public function logout(SessionInterface $session, Request $request, CloudService $cloud): Response
    {
        return $this->redirectToRoute('_home_index',['status' => $cloud->logout($session,$request)]);
    }
}
