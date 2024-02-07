<?php

namespace kirillemko\activeRecordHistory;


use kirillemko\activeRecordHistory\models\ActiveRecordHistory;
use yii\db\BaseActiveRecord;


class ActiveRecordHistoryAdder
{
    protected ActiveRecordHistory $historyElement;

    public function __construct()
    {
        $this->historyElement = new ActiveRecordHistory();
    }

    public function setModel(string $className): self
    {
        $this->historyElement->model = $className;
        return $this;
    }

    public function setModelId($modelId): self
    {
        $this->historyElement->model_id = $modelId;
        return $this;
    }

    public function setEvent(string $eventName): self
    {
        $this->historyElement->event = $eventName;
        return $this;
    }

    public function setOldValue($oldValue): self
    {
        $this->historyElement->old_value = $oldValue;
        return $this;
    }

    public function setNewValue($newValue): self
    {
        $this->historyElement->new_value = $newValue;
        return $this;
    }

    public function save(): bool
    {
        return $this->historyElement->save();
    }

}
