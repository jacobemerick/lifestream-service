<?php

namespace Jacobemerick\LifestreamService\Cron\Process;

use Exception;

use Jacobemerick\LifestreamService\Model\Event as EventModel;

trait ProcessTrait
{

    /** @var Container */
    protected $container;

    /**
     * @param EventModel $eventModel
     * @param string $type
     * @param integer $typeId
     * @return array
     */
    public function getEvent(EventModel $eventModel, $type, $typeId)
    {
        return $eventModel->getEventByTypeId($type, $typeId);
    }

    /**
     * @param EventModel $eventModel
     * @param string $description
     * @param string $descriptionHtml
     * @param DateTime $datetime
     * @param array metadata
     * @param string $type
     * @param integer $typeId
     * @return boolean
     */
    public function insertEvent(
        EventModel $eventModel,
        $description,
        $descriptionHtml,
        DateTime $datetime,
        array $metadata,
        $type,
        $typeId
    ) {
        return $eventModel->insertEvent(
            $description,
            $descriptionHtml,
            $datetime->format('Y-m-d H:i:s'),
            json_encode($metadata),
            $type,
            $typeId
        );
    }
}
