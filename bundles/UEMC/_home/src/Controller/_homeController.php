<?php

namespace UEMC\_home\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use UEMC\_home\Service\CloudService;

class _homeController extends AbstractController
{
    /**
     * @Route("/_home", name="_home_index")
     */
    public function index(CloudService $cloud): Response
    {
        return $this->render('@UEMC_homeBundle/index.html.twig');
    }
}
