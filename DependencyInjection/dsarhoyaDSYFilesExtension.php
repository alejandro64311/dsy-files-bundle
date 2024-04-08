<?php

namespace dsarhoya\DSYFilesBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class dsarhoyaDSYFilesExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (isset($config['s3']) && isset($config['s3']['credentials'])) {
            $container->setParameter('dsarhoya_dsy_files.s3.credentials', $config['s3']['credentials']);
        } else {
            $container->setParameter('dsarhoya_dsy_files.s3.credentials', null);
        }

        if (isset($config['s3']) && isset($config['s3']['named_credentials'])) {
            $container->setParameter('dsarhoya_dsy_files.s3.named_credentials', $config['s3']['named_credentials']);
        } else {
            $container->setParameter('dsarhoya_dsy_files.s3.named_credentials', null);
        }

        $container->setParameter('dsarhoya_dsy_files.storage_type', $config['storage_type']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
