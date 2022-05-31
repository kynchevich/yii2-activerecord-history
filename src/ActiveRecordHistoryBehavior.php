<?php

namespace kirillemko\activeRecordHistory;


use kirillemko\activeRecordHistory\models\ActiveRecordHistory;
use yii\data\Pagination;
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
     * @var bool|string Should I use model property to fill new_value of history on Insert event
     */
    public $newValuePropertyOnInsert = false;
    /**
     * @var bool|string Should I use model property to fill old_value of history on Delete event
     */
    public $oldValuePropertyOnDelete = false;

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

    /**
     * You can add custom events. For example afterRestore event of softDelete behaviour
     *
     * ```php
     *     'customEvents' => [
     *          'afterRestore' => [
     *              'oldValueProperty' => null,
     *              'newValueProperty' => 'question',
     *          ]
     *          ...
     *      ]
     * ```
     *
     * @var array
     */
    public $customEvents = [];


    /**
     * @var bool if paginate
     */
    public $paginate = true;

    /**
     * @var array format property values function to save
     */
    public $propertyValueFormatters = [];





    /** @var ActiveRecord|null */
    private $object = null;



    public function events()
    {
        $events = [];
        if ($this->watchInsertEvent) {
            $events[ActiveRecord::EVENT_AFTER_INSERT] = 'saveHistory';
        }
        if ($this->watchUpdateEvent) {
            $events[ActiveRecord::EVENT_AFTER_UPDATE] = 'saveHistory';
        }
        if ($this->watchDeleteEvent) {
            $events[ActiveRecord::EVENT_AFTER_DELETE] = 'saveHistory';
        }

        foreach ($this->customEvents as $customEventName => $customEventConfig) {
            $events[$customEventName] = 'saveHistory';
        }

        return $events;
    }


    public function getChangesHistory($sortAsc = true, $relations = [], Pagination $paginator=null)
    {
        $query = ActiveRecordHistory::find()
            ->orderBy(['id' => ($sortAsc ? SORT_ASC : SORT_DESC)])
            ->andWhere(['model' => get_class($this->owner)])
            ->andWhere(['model_id' => $this->owner->getPrimaryKey()]);

        if (!$relations) {
            return $query->all();
        }


        $modelWithRelations = get_class($this->owner)::find()
            ->andWhere($this->owner->getPrimaryKey(true))
            ->with($relations)
            ->one();

        // Фильтр только по тем релейшенам, которые запрашивались в истории
        $relationNames = [];
        foreach ($relations as $relation) {
            $relationNames = array_merge($relationNames, explode('.', $relation));
        }

        $relatedObjectsData = $this->getObjectRelatedRecordData($modelWithRelations, $relationNames);
        foreach ($relatedObjectsData as $relatedObjectsDatum) {
            $query->orWhere(['AND', $relatedObjectsDatum]);
        }

        if( $paginator ){
            $paginator->totalCount = $query->count();
            return  $query->offset($paginator->offset)
                ->limit($paginator->limit)
                ->all();
        }

        return $query->all();
    }

    private function getObjectRelatedRecordData(ActiveRecord $object, $filter=[])
    {
        $data = [[
            'model' => get_class($object),
            'model_id' => $object->getPrimaryKey()
        ]];
        foreach ($object->relatedRecords as $relationName => $relatedRecords) {
            if( $filter && !in_array($relationName, $filter) ){
                continue;
            }
            if( is_array($relatedRecords) ){
                foreach ($relatedRecords as $relatedRecord) {
                    $relatedData = $this->getObjectRelatedRecordData($relatedRecord, $filter);
                    $data = array_merge($data, $relatedData);
                }
            } else {
                $relatedData = $this->getObjectRelatedRecordData($relatedRecords, $filter);
                $data = array_merge($data, $relatedData);
            }
        }
        return $data;
    }


    /**
     * @param Event $event
     * @throws \Exception
     */
    public function saveHistory($event)
    {
        $this->object = $event->sender;

        switch ($event->name) {
            case ActiveRecord::EVENT_AFTER_INSERT:
                $this->saveHistoryModel(
                    $event->name,
                    $this->newValuePropertyOnInsert ?: null,
                    null,
                    $this->newValuePropertyOnInsert ? $this->object->{$this->newValuePropertyOnInsert} : null
                );

                if ($this->saveFieldsOnInsert) {
                    $this->saveHistoryModelAttributes($event->name, $event->changedAttributes);
                }
                break;
            case ActiveRecord::EVENT_AFTER_UPDATE:
                $this->saveHistoryModelAttributes($event->name, $event->changedAttributes);
                break;
            case ActiveRecord::EVENT_AFTER_DELETE:
                $this->saveHistoryModel(
                    $event->name,
                    $this->oldValuePropertyOnDelete ?: null,
                    $this->oldValuePropertyOnDelete ? $this->object->{$this->oldValuePropertyOnDelete} : null,
                    null
                );
                break;
            default:
                if( !$this->processCustomEvents($event->name) ){
                    throw new \Exception('Not found event!');
                }

        }
    }

    private function processCustomEvents($eventName): bool
    {
        foreach ($this->customEvents as $customEventName => $customEventConfig) {
            if( $customEventName !== $eventName ){
                continue;
            }
            $fieldName = $customEventConfig['oldValueProperty'] ?? null;
            if( !$fieldName ){
                $fieldName = $customEventConfig['newValueProperty'] ?? null;
            }
            $this->saveHistoryModel(
                $eventName,
                $fieldName,
                $this->object->{$customEventConfig['oldValueProperty']} ?? null,
                $this->object->{$customEventConfig['newValueProperty']} ?? null
            );
            return true;
        }
        return false;
    }

    private function saveHistoryModelAttributes($event, $changedAttributes = [])
    {
        foreach ($changedAttributes as $changedAttributeName => $oldValue) {
            if (in_array($changedAttributeName, $this->ignoreFields)) {
                continue;
            }
            $newValue = $this->object->$changedAttributeName;
            $this->saveHistoryModel($event, $changedAttributeName, $oldValue, $newValue);
        }
    }

    private function saveHistoryModel($event, $field_name = null, $old_value = null, $new_value = null)
    {
        $history = new ActiveRecordHistory();
        $history->event = $event;
        $history->field_name = $field_name;
        $history->old_value = $this->formatValueWithRules($field_name, $old_value);
        $history->new_value = $this->formatValueWithRules($field_name, $new_value);

        $history->model = get_class($this->object);
        $history->model_id = $this->object->getPrimaryKey();

        $history->save();
    }

    private function formatValueWithRules($field_name, $value)
    {
        if( !key_exists($field_name, $this->propertyValueFormatters) ){
            return $value;
        }
        return $this->propertyValueFormatters[$field_name]($this->object, $value);
    }


}
