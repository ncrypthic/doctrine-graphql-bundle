<?php

namespace LLA\DoctrineGraphQLBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class LLADoctrineGraphQLExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('lla.doctrine_graphql.cache_service', $config['cache_service']);
        $container->setParameter('lla.doctrine_graphql.entity_manager_service', $config['entity_manager_service']);
        $container->setParameter('lla.doctrine_graphql.logger_service', $config['logger_service']);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }
    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return 'lla_doctrine_graphql';
    }
}
