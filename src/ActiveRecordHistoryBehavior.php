<?php

namespace kirillemko\activeRecordHistory;


use kirillemko\activeRecordHistory\models\ActiveRecordHistory;
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
     * @var bool If insert event should be tracked
     */
    public $watchInsertEvent = true;
    /**
     * @var bool If update event should be tracked
     */
    public $watchUpdateEvent = true;
    /**
     * @var bool If delete event should be tracked
     */
    public $watchDeleteEvent = true;

    /** @var ActiveRecord|null */
    private $object = null;



    public function events()
    {
        $events = [];
        if( $this->watchInsertEvent ){
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


    public function getChangesHistory()
    {
        return ActiveRecordHistory::find()
            ->andWhere(['model' => get_class($this->owner)])
            ->andWhere(['model_id' => $this->owner->getPrimaryKey()])
            ->all();
    }
    

    /**
     * @param Event $event
     * @throws \Exception
     */
    public function saveHistory($event)
    {
        $this->object = $event->sender;

        switch ($event->name){
            case ActiveRecord::EVENT_AFTER_INSERT:
                $this->saveHistoryModel(ActiveRecordHistory::TYPE_INSERT);
                if( $this->saveFieldsOnInsert ){
                    $this->saveHistoryModelAttributes(ActiveRecordHistory::TYPE_UPDATE, $event->changedAttributes);
                }
                break;
            case ActiveRecord::EVENT_AFTER_UPDATE:
                $this->saveHistoryModelAttributes(ActiveRecordHistory::TYPE_UPDATE, $event->changedAttributes);
                break;
            case ActiveRecord::EVENT_AFTER_DELETE:
                $this->saveHistoryModel(ActiveRecordHistory::TYPE_DELETE);
                break;
            default:
                throw new \Exception('Not found event!');
        }
    }

    private function saveHistoryModelAttributes($type, $changedAttributes = [])
    {
        foreach ($changedAttributes as $changedAttributeName => $oldValue) {
            if( in_array($changedAttributeName, $this->ignoreFields) ){
                continue;
            }
            $newValue = $this->object->$changedAttributeName;
            $this->saveHistoryModel($type, $changedAttributeName, $oldValue, $newValue);
        }
    }

    private function saveHistoryModel($type, $field_name=null, $old_value=null, $new_value=null)
    {
        $history = new ActiveRecordHistory();
        $history->type = $type;
        $history->field_name = $field_name;
        $history->old_value = $old_value;
        $history->new_value = $new_value;

        $history->model = get_class($this->object);
        $history->model_id = $this->object->getPrimaryKey();

        $history->save();
    }


}
