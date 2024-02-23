<?php

namespace UEMC\Core\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use UEMC\Core\Service\CloudService;

class coreController extends AbstractController
{
    /**
     * @Route("/core", name="core_index")
     */
    public function index(CloudService $cloud): Response
    {
        return $this;
    }

}