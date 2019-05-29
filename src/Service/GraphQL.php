<?php
namespace LLA\DoctrineGraphQLBundle\Service;

use Doctrine\ORM\EntityManager;
use GraphQL\Server\Helper;
use GraphQL\Server\ServerConfig;
use LLA\DoctrineGraphQL\DoctrineGraphQL;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class GraphQL implements CacheWarmerInterface, CacheClearerInterface
{
    /**
     * @var Symfony\Component\Cache\Adapter\AdapterInterface
     */
    private $cache;
    /**
     * @var LLA\DoctrineGraphQL\DoctrineGraphQL
     */
    private $schema;
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $entityManager;

    public function __construct(AdapterInterface $cache, EntityManager $entityManager)
    {
        $this->cache = $cache;
        $this->entityManager = $entityManager;
        $this->schema = new DoctrineGraphQL();
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
     * @return GraphQL\Executor\ExecutionResult
     */
    public function handleRequest(Request $req)
    {
        $helper = new Helper();
        $operations = $helper->parseHttpRequest(function() use($req) {
            return $req->getContent();
        });
        $config = $this->getCachedServerConfig();
        return is_array($operations)
            ? $helper->executeBatch($config, $operations)
            : $helper->executeOperation($config, $operations);
    }
    /**
     * Get GraphQL server config
     *
     * @return GraphQL\Server\ServerConfig
     */
    private function getCachedServerConfig()
    {
        $cachedConfig = $this->cache->getItem('lla.doctrine_graphql.config');
        if(!$cachedConfig->isHit()) {
            $schema = $this->schema
                ->buildTypes($this->entityManager)
                ->buildQueries($this->entityManager)
                ->buildMutations($this->entityManager)
                ->toGraphQLSchema();
            $cachedConfig->set(ServerConfig::create(['schema' => $schema]));
        }
        return $cachedConfig->get();
    }
    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDirectory)
    {
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

