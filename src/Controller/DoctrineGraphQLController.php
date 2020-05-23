<?php

namespace LLA\DoctrineGraphQLBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DoctrineGraphQLController extends Controller
{
    public function graphqlAction(request $req)
    {
        return new JsonResponse($this->get('lla.doctrine_graphql.service.graphql')->handleRequest($req));
    }

    public function corsAction(Request $req)
    {
        return new Response(null, 200, [
            'access-control-allow-origin' => $req->headers->get('Origin', '') ?? '',
            'access-control-allow-method' => 'GET,POST',
            'access-control-allow-header' => 'Authorization,Content-Type,Accept'
        ]);
    }
}
