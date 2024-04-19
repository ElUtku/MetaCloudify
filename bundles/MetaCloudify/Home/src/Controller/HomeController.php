<?php

namespace MetaCloudify\Home\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use MetaCloudify\Home\Service\CloudService;

class HomeController extends AbstractController
{
    /**
     * @Route("/Home", name="Home_index")
     */
    public function index(): Response
    {
        return $this->render('@MetaCloudifyHomeBundle/index.html.twig');
    }

    /**
     * @Route("/Home/about", name="Home_about")
     */
    public function about(): Response
    {
        return $this->render('@MetaCloudifyHomeBundle/about.html.twig');
    }

    /**
     * @Route("/Home/provacidadCondiciones", name="Home_provacidad_condiciones")
     */
    public function provacidadCondiciones(): Response
    {
        return $this->render('@MetaCloudifyHomeBundle/service_termines_privacity.html.twig');
    }
}
