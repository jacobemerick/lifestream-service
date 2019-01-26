<?php

namespace Jacobemerick\LifestreamService\Controller;

use AvalancheDevelopment\Peel\HttpError\NotFound;
use Interop\Container\ContainerInterface as Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class Event
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
    public function getEvent(Request $request, Response $response)
    {
        $eventId = $request->getAttribute('swagger')->getParams()['event_id']['value'];

        $event = $this->container->get('eventModel')->findById($eventId);
        if (!$event) {
            throw new NotFound('No event found');
        }

        $event = $this->container->get('eventSerializer')->__invoke($event);
        $event = json_encode($event);
        $response->getBody()->write($event);

        return $response;
    }

    public function getEvents() {}
}
