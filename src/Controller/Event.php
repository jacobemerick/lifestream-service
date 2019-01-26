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

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function getEvents(Request $request, Response $response)
    {
        $limit = 0;
        $offset = 0;
        $user = '';
        $type = '';
        $order = '';
        $isAscending = true;

        $query = $request->getAttribute('swagger')->getParams();
        if (array_key_exists('per_page', $query)) {
            $limit = $query['per_page']['value'];
        }
        if (array_key_exists('page', $query)) {
            $offset = ($query['page']['value'] - 1) * $limit;
        }
        if (array_key_exists('user', $query)) {
            $user = $query['user']['value'];
        }
        if (array_key_exists('type', $query)) {
            $type = $query['type']['value'];
        }
        if (array_key_exists('sort', $query)) {
            $order = $query['sort']['value'];
            if (substr($order, 0, 1) == '-') {
                $isAscending = false;
                $order = substr($order, 1);
            }
        }

        $events = $this->container
            ->get('eventModel')
            ->getEvents(
                $limit,
                $offset,
                $type,
                $user,
                $order,
                $isAscending
            );

        $events = array_map(
            $this->container->get('eventSerializer'),
            $events
        );

        $events = json_encode($events);
        $response->getBody()->write($events);

        return $response;
    }
}
