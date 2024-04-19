<?php

namespace MetaCloudify\_home\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use MetaCloudify\_home\Service\CloudService;

class _homeController extends AbstractController
{
    /**
     * @Route("/_home", name="_home_index")
     */
    public function index(): Response
    {
        return $this->render('@MetaCloudify_homeBundle/index.html.twig');
    }

    /**
     * @Route("/_home/about", name="_home_about")
     */
    public function about(): Response
    {
        return $this->render('@MetaCloudify_homeBundle/about.html.twig');
    }

    /**
     * @Route("/_home/provacidadCondiciones", name="_home_provacidad_condiciones")
     */
    public function provacidadCondiciones(): Response
    {
        return $this->render('@MetaCloudify_homeBundle/service_termines_privacity.html.twig');
    }
}
