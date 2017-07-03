<?php

namespace Jacobemerick\LifestreamService\Controller;

use Interop\Container\ContainerInterface as Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class Type
{

    /** @var Container */
    protected $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function getTypes(Request $request, Response $response)
    {
        $types = $this->container
            ->get('typeModel')
            ->getTypes();

        $types = array_map($this->container->get('typeSerializer'), $types);
        $types = json_encode($types);
        $response->getBody()->write($types);
        return $response;
    }
}
