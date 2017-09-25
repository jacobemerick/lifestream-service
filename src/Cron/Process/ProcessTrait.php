<?php

namespace Jacobemerick\LifestreamService\Cron\Process;

use DateTime;
use Exception;

use Jacobemerick\LifestreamService\Model\Event as EventModel;
use Jacobemerick\LifestreamService\Model\Type as TypeModel;

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
     * @param TypeModel $typeModel
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
        TypeModel $typeModel,
        $description,
        $descriptionHtml,
        DateTime $datetime,
        array $metadata,
        $type,
        $typeId
    ) {
        $type = $typeModel->getTypeId($type);

        return $eventModel->insertEvent(
            $description,
            $descriptionHtml,
            $datetime,
            json_encode($metadata),
            $type,
            $typeId
        );
    }
}
