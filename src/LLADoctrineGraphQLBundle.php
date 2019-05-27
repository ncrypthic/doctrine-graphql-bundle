<?php

namespace LLA\DoctrineGraphQLBundle;

use LLA\DoctrineGraphQLBundle\DependencyInjection\LLADoctrineGraphQLExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class LLADoctrineGraphQLBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new LLADoctrineGraphQLExtension();
    }
}
