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
    MetaCloudify\OneDriveBundle\MetaCloudifyOneDriveBundle::class => ['all' => true],
    MetaCloudify\OwnCloudBundle\MetaCloudifyOwnCloudBundle::class => ['all' => true],
    MetaCloudify\GoogleDriveBundle\MetaCloudifyGoogleDriveBundle::class => ['all' => true],
    MetaCloudify\FtpBundle\MetaCloudifyFtpBundle::class => ['all' => true],
    MetaCloudify\HomeBundle\MetaCloudifyHomeBundle::class => ['all' => true],
    MetaCloudify\CoreBundle\MetaCloudifyCoreBundle::class => ['all' => true],
    Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle::class => ['all' => true],
];
