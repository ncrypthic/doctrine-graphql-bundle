<?php
namespace LLA\DoctrineGraphQLBundle\Service;

use Doctrine\ORM\EntityManager;
use GraphQL\Error\Debug;
use GraphQL\Server\Helper;
use GraphQL\Server\ServerConfig;
use LLA\DoctrineGraphQL\DoctrineGraphQL;
use LLA\DoctrineGraphQL\Type\Registry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use \sprintf;

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
     * @var \LLA\DoctrineGraphQL\Type\Registry;
     */
    private $registry;
    /**
     * @var boolean
     */
    private $debug;

    public function __construct(AdapterInterface $cache, EntityManager $entityManager, LoggerInterface $logger, $debug=false)
    {
        $this->cache = $cache;
        $this->entityManager = $entityManager;
        $this->registry = new Registry();
        $this->schema = new DoctrineGraphQL($this->registry, $this->entityManager, null);
        $this->logger = $logger;
        $this->debug = $debug;
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
        $operations = $helper->parseHttpRequest(function() use($req) {
            return $req->getContent();
        });
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
            $schema = $this->schema
                ->buildTypes($this->entityManager)
                ->buildQueries($this->entityManager)
                ->buildMutations($this->entityManager)
                ->toGraphQLSchema();
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
}

