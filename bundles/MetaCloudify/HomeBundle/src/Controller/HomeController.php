<?php

namespace MetaCloudify\HomeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use MetaCloudify\HomeBundle\Service\CloudService;

class HomeController extends AbstractController
{
    /**
     * @Route("Home", name="Home_index")
     */
    public function index(): Response
    {
        $bundles = $this->getParameter('kernel.bundles');

        $ftpAvailable = false;
        $googleDriveAvailable = false;
        $oneDriveAvailable = false;
        $ownCloudAvailable = false;

// Se Verifica qué bundles están instalados y actualizar las variables correspondientes
        if (isset($bundles['MetaCloudifyFtpBundle'])) {
            $ftpAvailable = true;
        }

        if (isset($bundles['MetaCloudifyGoogleDriveBundle'])) {
            $googleDriveAvailable = true;
        }

        if (isset($bundles['MetaCloudifyOneDriveBundle'])) {
            $oneDriveAvailable = true;
        }

        if (isset($bundles['MetaCloudifyOwnCloudBundle'])) {
            $ownCloudAvailable = true;
        }

        // Renderizar la plantilla Twig con las variables disponibles
        return $this->render('@MetaCloudifyHomeBundle/index.html.twig', [
            'ftpAvailable' => $ftpAvailable,
            'googleDriveAvailable' => $googleDriveAvailable,
            'oneDriveAvailable' => $oneDriveAvailable,
            'ownCloudAvailable' => $ownCloudAvailable,
        ]);
    }

    /**
     * @Route("Home/about", name="Home_about")
     */
    public function about(): Response
    {
        return $this->render('@MetaCloudifyHomeBundle/about.html.twig');
    }

    /**
     * @Route("Home/provacidadCondiciones", name="Home_provacidad_condiciones")
     */
    public function provacidadCondiciones(): Response
    {
        return $this->render('@MetaCloudifyHomeBundle/service_termines_privacity.html.twig');
    }
}
