<?php

namespace LLA\DoctrineGraphQLBundle;

use LLA\DoctrineGraphQLBundle\DependencyInjection\Compiler\EventDispatcherPass;
use LLA\DoctrineGraphQLBundle\DependencyInjection\LLADoctrineGraphQLExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class LLADoctrineGraphQLBundle extends Bundle
{
    public function build(ContainerBuilder $builder)
    {
        parent::build($builder);
        $builder->addCompilerPass(new EventDispatcherPass());
    }
    public function getContainerExtension()
    {
        return new LLADoctrineGraphQLExtension();
    }
}
