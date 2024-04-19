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
    MetaCloudify\OneDrive\MetaCloudifyOneDriveBundle::class => ['all' => true],
    MetaCloudify\OwnCloud\MetaCloudifyOwnCloudBundle::class => ['all' => true],
    MetaCloudify\GoogleDrive\MetaCloudifyGoogleDriveBundle::class => ['all' => true],
    MetaCloudify\Ftp\MetaCloudifyFtpBundle::class => ['all' => true],
    MetaCloudify\Home\MetaCloudifyHomeBundle::class => ['all' => true],
    MetaCloudify\Core\MetaCloudifyCoreBundle::class => ['all' => true],
    Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle::class => ['all' => true],
];
