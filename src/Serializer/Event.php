<?php

namespace Jacobemerick\LifestreamService\Serializer;

use DateTime;

class Event
{

    /**
     * @param array $event
     * @return array
     */
    public function __invoke(array $event)
    {
        return [
            'id' => $event['id'],
            'description' => $event['description'],
            'description_html' => $event['description_html'],
            'metadata' => json_decode($event['metadata'], true),
            'date' => (new DateTime($event['date']))->format('c'),
            'user' => $event['user_name'],
            'type' => $event['type_name'],
        ];
    }
}
