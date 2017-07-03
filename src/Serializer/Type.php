<?php

namespace Jacobemerick\LifestreamService\Serializer;

class Type
{

    /**
     * @param array $type
     * @return array
     */
    public function __invoke(array $type)
    {
        return [
            'id' => $type['id'],
            'name' => $type['name'],
        ];
    }
}
