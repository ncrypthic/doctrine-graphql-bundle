<?php
namespace LLA\DoctrineGraphQLBundle\Service;

use Doctrine\ORM\EntityManager;
use GraphQL\Error\Debug;
use GraphQL\Server\Helper;
use GraphQL\Server\ServerConfig;
use LLA\DoctrineGraphQL\DoctrineGraphQL;
use LLA\DoctrineGraphQL\Type\RegistryInterface;
use LLA\DoctrineGraphQLBundle\Event\SchemaGenerationEvent;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class GraphQL implements CacheWarmerInterface, CacheClearerInterface
{
    /**
     * @var \Symfony\Component\Cache\Adapter\AdapterInterface
     */
    private $cache;
    /**
     * @var \LLA\DoctrineGraphQL\DoctrineGraphQL
     */
    private $schema;
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    /**
     * @var \LLA\DoctrineGraphQL\Type\RegistryInterface
     */
    private $registry;
    /**
     * @var PsrHttpFactory
     */
    private $psrFactory;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @var boolean
     */
    private $debug;

    public function __construct(
        RegistryInterface $registry, AdapterInterface $cache, EntityManager $entityManager,
        LoggerInterface $logger, EventDispatcherInterface $eventDispatcher, $debug = false)
    {
        $this->registry = $registry;
        $this->cache = $cache;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
        $this->debug = $debug;
        $this->schema = new DoctrineGraphQL($registry, $this->entityManager, null);
        $psr17Factory = new Psr17Factory();
        $this->psrFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
    }
    /**
     * {@inheritdoc}
     */
    public function isOptional(): bool
    {
        return false;
    }
    /**
     * @param Request $req
     * @return \GraphQL\Executor\ExecutionResult
     */
    public function handleRequest(Request $req)
    {
        $helper = new Helper();
        $psrRequest = $this->psrFactory->createRequest($req);
        $operations = $helper->parsePsrRequest($psrRequest);
        $this->logger->debug('Handling GraphQL operations', ['operations' => $operations]);
        $config = $this->getCachedServerConfig();
        return is_array($operations)
            ? $helper->executeBatch($config, $operations)
            : $helper->executeOperation($config, $operations);
    }
    /**
     * Get GraphQL server config
     *
     * @return \GraphQL\Server\ServerConfig
     */
    private function getCachedServerConfig()
    {
        $cachedConfig = $this->cache->getItem('lla.doctrine_graphql.config');
        if(!$cachedConfig->isHit()) {
            $this->logger->debug('no cached config, building new schema');
            $this->schema
                ->buildTypes($this->entityManager)
                ->buildQueries($this->entityManager)
                ->buildMutations($this->entityManager);
            $this->eventDispatcher->dispatch('lla.doctrine_graphql.event.schema_generation', new SchemaGenerationEvent($this->registry));
            $schema = $this->schema->toGraphqlSchema();
            $config = ServerConfig::create([
                'schema' => $schema,
                'debug' => $this->debug ? Debug::INCLUDE_DEBUG_MESSAGE | Debug::INCLUDE_TRACE : false,
            ]);
            $cachedConfig->set($config);
            $this->logger->debug('Caching schema configuration', [ 'result' => $this->cache->save($cachedConfig) ]);
        }
        return $cachedConfig->get();
    }
    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDirectory)
    {
        $this->logger->debug('Warming up cache');
        $this->getCachedServerConfig();
    }
    /**
     * {@inheritdoc}
     */
    public function clear($cacheDirectory)
    {
        $this->cache->deleteItem('lla.doctrine_graphql.config');
    }
    /**
     * Register a custom GraphQL query field
     *
     * @param string $queryName GraphQL query name
     * @param array $queryDefinition See: https://webonyx.github.io/graphql-php/type-system/schema/#query-and-mutation-types
     * @return GraphQL
     */
    public function registerQuery(string $queryName, array $queryDefinition): self
    {
        if (isset($this->customQueries[$queryName])) {
            throw new \InvalidArgumentException("Cannot replace existing GraphQL query field, `$queryName`");
        }
        $this->customQueries[$queryName] = $queryDefinition;

        return $this;
    }
    /**
     * Register a custom GraphQL mutation field
     *
     * @param string $queryName GraphQL mutation name
     * @param array $queryDefinition See: https://webonyx.github.io/graphql-php/type-system/schema/#query-and-mutation-types
     * @return GraphQL
     */
    public function registerMutation(string $mutationName, array $mutationDefinition): self
    {
        if (isset($this->customMutations[$mutationName])) {
            throw new \InvalidArgumentException("Cannot replace existing GraphQL query field, `$mutationName`");
        }
        $this->customQueries[$mutationName] = $mutationDefinition;

        return $this;
    }
}

