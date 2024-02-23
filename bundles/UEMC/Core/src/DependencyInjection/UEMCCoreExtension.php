<?php

namespace UEMC\Core\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class UEMCCoreExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
    }
}