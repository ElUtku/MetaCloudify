<?php

namespace UEMC\OwnCloud\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class UEMCOwnCloudExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
    }
}