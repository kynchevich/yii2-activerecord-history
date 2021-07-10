<?php

namespace kirillemko\activeRecordHistory\models;


use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;

/**
 * @property $id
 * @property $user_id
 * @property $type
 * @property $model
 * @property $model_id
 * @property $field_name
 * @property $old_value
 * @property $new_value
 * @property $created_at
 */
class ActiveRecordHistory extends \yii\db\ActiveRecord
{

    const TYPE_INSERT = 1;
    const TYPE_UPDATE = 2;
    const TYPE_DELETE = 3;

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
            ],
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'user_id',
                'updatedByAttribute' => false,
            ]
        ];
    }

}
