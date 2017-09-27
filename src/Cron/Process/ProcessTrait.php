<?php

namespace Jacobemerick\LifestreamService\Cron\Process;

use DateTime;
use Exception;

use Jacobemerick\LifestreamService\Model\Event as EventModel;
use Jacobemerick\LifestreamService\Model\Type as TypeModel;
use Jacobemerick\LifestreamService\Model\User as UserModel;

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
     * @param UserModel $userModel
     * @param string $description
     * @param string $descriptionHtml
     * @param DateTime $datetime
     * @param array metadata
     * @param string $user
     * @param string $type
     * @param integer $typeLookupId
     * @return boolean
     */
    public function insertEvent(
        EventModel $eventModel,
        TypeModel $typeModel,
        UserModel $userModel,
        $description,
        $descriptionHtml,
        DateTime $datetime,
        array $metadata,
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
}
