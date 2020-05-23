<?php
namespace LLA\DoctrineGraphQLBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EventDispatcherPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $dispatcher = $container->getDefinition('event_dispatcher');
        $serviceIds = $container->findTaggedServiceIds('lla.doctrine_graphql.event_listener');
        foreach($serviceIds as $serviceId => $tags) {
            foreach($tags as $tag) {
                $priority = isset($tag['priority']) ? $tag['priority'] : 0;
                $dispatcher->addMethodCall(
                    'addListener',
                    [$tag['event'], [new ServiceClosureArgument(new Reference($serviceId)), $tag['method']], $priority]);
            }
        }
    }
}
