<?php

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
    Symfony\Bundle\DebugBundle\DebugBundle::class => ['dev' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class => ['dev' => true, 'test' => true],
    Twig\Extra\TwigExtraBundle\TwigExtraBundle::class => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
    Symfony\Bundle\MakerBundle\MakerBundle::class => ['dev' => true],
    UEMC\Core\UEMCCoreBundle::class => ['all' => true],
    UEMC\OneDrive\UEMCOneDriveBundle::class => ['all' => true],
    UEMC\OwnCloud\UEMCOwnCloudBundle::class => ['all' => true],
    UEMC\GoogleDrive\UEMCGoogleDriveBundle::class => ['all' => true],
    UEMC\Ftp\UEMCFtpBundle::class => ['all' => true],
    UEMC\_home\UEMC_homeBundle::class => ['all' => true],
];
