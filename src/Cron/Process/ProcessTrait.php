<?php

namespace Jacobemerick\LifestreamService\Cron\Process;

use DateTime;
use Exception;
use stdclass;

use Jacobemerick\LifestreamService\Model\Event as EventModel;
use Jacobemerick\LifestreamService\Model\Type as TypeModel;
use Jacobemerick\LifestreamService\Model\User as UserModel;

trait ProcessTrait
{

    /**
     * @param EventModel $eventModel
     * @param string $type
     * @param integer $typeId
     * @return stdclass
     */
    protected function getEvent(EventModel $eventModel, $type, $typeId)
    {
        return $eventModel->getEventByTypeId($type, $typeId);
    }

    /**
     * @param EventModel $eventModel
     * @param TypeModel $typeModel
     * @param UserModel $userModel
     * @param string $description
     * @param string $descriptionHtml
     * @param DateTime $datetime
     * @param stdclass $metadata
     * @param string $user
     * @param string $type
     * @param integer $typeLookupId
     * @return boolean
     */
    protected function insertEvent(
        EventModel $eventModel,
        TypeModel $typeModel,
        UserModel $userModel,
        $description,
        $descriptionHtml,
        DateTime $datetime,
        stdclass $metadata,
        $user,
        $type,
        $typeLookupId
    ) {
        $typeId = $typeModel->getTypeId($type);
        $userId = $userModel->getUserId($user);

        return $eventModel->insertEvent(
            $description,
            $descriptionHtml,
            $datetime,
            json_encode($metadata),
            $userId,
            $typeId,
            $typeLookupId
        );
    }

    /**
     * @param EventModel $eventModel
     * @param integer $id
     * @param stdclass $metadata
     * @return boolean
     */
    protected function updateEventMetadata(EventModel $eventModel, $id, stdclass $metadata)
    {
        return $eventModel->updateEventMetadata($id, json_encode($metadata));
    }
}
