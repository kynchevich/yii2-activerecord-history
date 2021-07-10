<?php

namespace kirillemko\activeRecordHistory\models;


use App\domain\ACL\models\AclGroupsPermissions;
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
    public static function tableName()
    {
        return 'activerecord_models_history';
    }


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

    public function fields()
    {
        return array_merge(parent::fields(),[
            'field_full_name' => function() {
                return $this->model::instance()->getAttributeLabel($this->field_name);
            },
            'type_full_name' => function() {
                switch ($this->type){
                    case ActiveRecordHistory::TYPE_INSERT:
                        return 'Создание';
                        break;
                    case ActiveRecordHistory::TYPE_UPDATE:
                        return 'Редактирование';
                        break;
                    case ActiveRecordHistory::TYPE_DELETE:
                        return 'Удаление';
                        break;
                }
                return '';
            }
        ]);
    }


}
