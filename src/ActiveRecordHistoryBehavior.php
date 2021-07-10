<?php

namespace kirillemko\activeRecordHistory;


use yii\db\ActiveRecord;
use \yii\base\Behavior;


class ActiveRecordHistoryBehavior extends Behavior
{

    /**
     * @var array This fields will not be trackable
     */
    public $ignoreFields = [];
    
    /**
     * @var bool Save every inserted field on INSERT
     */
    public $saveFieldsOnInsert = false;

    /**
     * @var bool If create event should be tracked
     */
    public $watchCreateEvent = true;
    /**
     * @var bool If update event should be tracked
     */
    public $watchUpdateEvent = true;
    /**
     * @var bool If delete event should be tracked
     */
    public $watchDeleteEvent = true;



    public function events()
    {
        $events = [];
        if( $this->watchCreateEvent ){
            $events[ActiveRecord::EVENT_AFTER_INSERT] = 'saveHistory';
        }
        if( $this->watchUpdateEvent ){
            $events[ActiveRecord::EVENT_AFTER_UPDATE] = 'saveHistory';
        }
        if( $this->watchDeleteEvent ){
            $events[ActiveRecord::EVENT_AFTER_DELETE] = 'saveHistory';
        }

        return $events;
    }

    /**
     * @param Event $event
     * @throws \Exception
     */
    public function saveHistory($event)
    {
        switch ($event->name){
            case ActiveRecord::EVENT_AFTER_INSERT:
                if( $this->saveFieldsOnInsert ){
                    foreach ($event->changedAttributes as $changedAttribute) {
                        
                    }
                }
                
                $history->type = ActiveRecordHistory::TYPE_INSERT;
                
                
                $type = $manager::AR_INSERT;
                $manager->setUpdatedFields($event->changedAttributes);
                break;

            case BaseActiveRecord::EVENT_AFTER_UPDATE:

                if (in_array(BaseManager::AR_UPDATE_PK, $this->eventsList) && ($this->owner->getOldPrimaryKey() != $this->owner->getPrimaryKey()))
                    $type = $manager::AR_UPDATE_PK;
                elseif (in_array(BaseManager::AR_UPDATE, $this->eventsList))
                    $type = $manager::AR_UPDATE;
                else
                    return true;

                $changedAttributes = $event->changedAttributes;
                foreach ($this->ignoreFields as $ignoreField)
                    if (isset($changedAttributes[$ignoreField]))
                        unset($changedAttributes[$ignoreField]);

                $manager->setUpdatedFields($changedAttributes);
                break;

            case BaseActiveRecord::EVENT_AFTER_DELETE:
                $type = $manager::AR_DELETE;
                break;

            default:
                throw new \Exception('Not found event!');
        }
        $manager->run($type, $this->owner);
    }

    private function saveHistoryModel($type, $attributes = [])
    {
        $history = new ActiveRecordHistory();
        $history->type = $type;
    }


}
