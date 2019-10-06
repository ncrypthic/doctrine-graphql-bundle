<?php

namespace LLA\DoctrineGraphQLBundle\Controller;

use LLA\DoctrineGraphQLBundle\Service\GraphQL;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DoctrineGraphQLController extends AbstractController
{
    public function graphqlAction(Request $req)
    {
        $graphql = $this->container->get('lla_doctrine_graphql.service.graphql');
        return new JsonResponse($graphql->handleRequest($req));
    }
}
