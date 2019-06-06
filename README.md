# Doctrine GraphQL Bundle

This is a Symfony bundle for [ncrypthic/doctrine-graphql](https://packagist.org/packages/ncrypthic/doctrine-graphql) library

## Installation

```sh
composer require ncrypthic/doctrine-graphql-bundle
```

## Configuration

1. Enable the bundle in Symfony's `AppKernel`

```php
<?php
// file: app/AppKernel.php

    public function registerBundles()
    {
        $bundles = array(
            // ... other bundles
            new LLA\DoctrineGraphQLBundle\DoctrineGraphQLBundle(),
        );
    }
```

2. Configure the bundle

```yaml
# file: app/config/config.yml
lla_doctrine_graphql:
    cache_service: cache.app # default
    entity_manager_service: doctrine.orm.default_entity_manager
```

3. Add graphql route

```yaml
    graphql:
        path: /graphql
        method: POST
        defaults: { _controller: LLADoctrineGraphQLBundle:DoctrineGraphQL:graphql }
```
