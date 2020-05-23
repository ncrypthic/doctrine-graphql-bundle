<?php
namespace LLA\DoctrineGraphQLBundle\Event;

use LLA\DoctrineGraphQL\Type\RegistryInterface;
use Symfony\Component\EventDispatcher\Event as DispatcherEvent;

class SchemaGenerationEvent extends DispatcherEvent
{
    public const NAME = 'schema_generation';
    /**
     * @var RegistryInterface
     */
    private $registry;

    public function __construct(RegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    public function getRegistry(): RegistryInterface
    {
        return $this->registry;
    }
}
