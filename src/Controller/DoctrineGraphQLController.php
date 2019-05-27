<?php

namespace LLA\DoctrineGraphQLBundle\Controller;

use GraphQL\Server\Helper;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DoctrineGraphQLController extends Controller
{
    public function graphqlAction(Request $req)
    {
        return new JsonResponse($this->get('lla.doctrine_graphql.service.graphql')->handleRequest($req));
    }
}
