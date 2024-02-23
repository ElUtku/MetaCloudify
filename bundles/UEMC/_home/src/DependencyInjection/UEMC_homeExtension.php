<?php

namespace UEMC\_home\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class UEMC_homeExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
    }
}